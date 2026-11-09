"""
SEO Automation FastMCP Server
Provides integration with Google Search Console and Bing Webmaster Tools using FastMCP.
"""

import os
from datetime import datetime, timedelta
from typing import Optional, List, Dict, Any

from fastmcp import FastMCP
from google.oauth2 import service_account
from googleapiclient.discovery import build
import requests

# Initialize FastMCP Server
mcp = FastMCP(name="seo-automation")

GSC_KEY_PATH = os.path.expanduser(
    os.environ.get("GSC_CREDENTIALS_PATH", "~/.config/sipswpcalculator/secrets/gsc-key.json")
)
DEFAULT_SITE_URL = os.environ.get("GSC_SITE_URL", "sc-domain:sipswpcalculator.com")
BING_API_KEY = os.environ.get("BING_API_KEY", "")
GA4_PROPERTY_ID = os.environ.get("GA4_PROPERTY_ID", "")
GA4_SCOPES = ["https://www.googleapis.com/auth/analytics.readonly"]


def get_gsc_service():
    if not os.path.exists(GSC_KEY_PATH):
        raise FileNotFoundError(f"GSC credentials file not found at {GSC_KEY_PATH}")
    
    scopes = ["https://www.googleapis.com/auth/webmasters.readonly"]
    creds = service_account.Credentials.from_service_account_file(
        GSC_KEY_PATH, scopes=scopes
    )
    return build("searchconsole", "v1", credentials=creds)


def get_ga4_service():
    if not os.path.exists(GSC_KEY_PATH):
        raise FileNotFoundError(f"Service account credentials file not found at {GSC_KEY_PATH}")
    
    creds = service_account.Credentials.from_service_account_file(
        GSC_KEY_PATH, scopes=GA4_SCOPES
    )
    return build("analyticsdata", "v1beta", credentials=creds)


def get_ga4_admin_service():
    if not os.path.exists(GSC_KEY_PATH):
        raise FileNotFoundError(f"Service account credentials file not found at {GSC_KEY_PATH}")
    
    creds = service_account.Credentials.from_service_account_file(
        GSC_KEY_PATH, scopes=GA4_SCOPES
    )
    return build("analyticsadmin", "v1beta", credentials=creds)


def resolve_ga4_property_id(explicit_id: Optional[str] = None) -> Optional[str]:
    """Resolve GA4 Property ID from explicit argument, environment variable, or auto-discovery."""
    if explicit_id:
        return explicit_id.replace("properties/", "").strip()
    
    env_id = os.environ.get("GA4_PROPERTY_ID", GA4_PROPERTY_ID).replace("properties/", "").strip()
    if env_id:
        return env_id
        
    try:
        admin_service = get_ga4_admin_service()
        response = admin_service.accountSummaries().list().execute()
        for account in response.get("accountSummaries", []):
            for prop in account.get("propertySummaries", []):
                prop_id = prop.get("property", "").replace("properties/", "").strip()
                if prop_id:
                    return prop_id
    except Exception:
        pass
        
    return None


@mcp.tool
def gsc_list_sites() -> List[Dict[str, Any]]:
    """List all verified sites in Google Search Console that this service account has access to."""
    service = get_gsc_service()
    sites_response = service.sites().list().execute()
    return sites_response.get("siteEntry", [])


@mcp.tool
def gsc_query_performance(
    site_url: Optional[str] = None,
    days: int = 28,
    dimensions: Optional[List[str]] = None,
    row_limit: int = 50,
    start_row: int = 0
) -> Dict[str, Any]:
    """
    Query search performance metrics (clicks, impressions, CTR, position) from Google Search Console.
    
    Args:
        site_url: The URL or domain property (e.g. 'sc-domain:sipswpcalculator.com' or 'https://sipswpcalculator.com/').
                  Defaults to the configured GSC_SITE_URL.
        days: Number of days back from yesterday to query (e.g. 7, 28, 90).
        dimensions: Dimensions to group by: 'query', 'page', 'country', 'device', 'date'. Defaults to ['query'].
        row_limit: Maximum number of rows to return (up to 5000).
        start_row: Zero-based row offset for pagination.
    """
    target_site = site_url or DEFAULT_SITE_URL
    if dimensions is None:
        dimensions = ["query"]
        
    service = get_gsc_service()
    
    end_date = (datetime.utcnow() - timedelta(days=2)).strftime("%Y-%m-%d")
    start_date = (datetime.utcnow() - timedelta(days=days + 2)).strftime("%Y-%m-%d")
    
    request_body = {
        "startDate": start_date,
        "endDate": end_date,
        "dimensions": dimensions,
        "rowLimit": min(row_limit, 5000),
        "startRow": start_row
    }
    
    response = service.searchanalytics().query(siteUrl=target_site, body=request_body).execute()
    rows = response.get("rows", [])
    
    formatted_rows = []
    for r in rows:
        formatted_rows.append({
            "keys": r.get("keys", []),
            "clicks": r.get("clicks", 0),
            "impressions": r.get("impressions", 0),
            "ctr": round(r.get("ctr", 0.0) * 100, 2),
            "position": round(r.get("position", 0.0), 1)
        })
        
    return {
        "site_url": target_site,
        "start_date": start_date,
        "end_date": end_date,
        "dimensions": dimensions,
        "total_rows": len(formatted_rows),
        "rows": formatted_rows
    }


@mcp.tool
def gsc_inspect_url(url: str, site_url: Optional[str] = None) -> Dict[str, Any]:
    """
    Inspect indexation status, crawl issues, and canonical URL of a specific page via GSC URL Inspection API.
    
    Args:
        url: The full URL to inspect (e.g. 'https://sipswpcalculator.com/sip-calculator').
        site_url: The site property as registered in GSC. Defaults to configured site.
    """
    target_site = site_url or DEFAULT_SITE_URL
    service = get_gsc_service()
    
    body = {
        "inspectionUrl": url,
        "siteUrl": target_site
    }
    return service.urlInspection().index().inspect(body=body).execute()


@mcp.tool
def bing_get_query_stats(site_url: str, api_key: Optional[str] = None) -> Dict[str, Any]:
    """
    Get keyword query metrics and impressions from Bing Webmaster Tools API.
    
    Args:
        site_url: The site URL registered in Bing Webmaster Tools (e.g. 'https://sipswpcalculator.com').
        api_key: Bing Webmaster API Key (optional if configured in environment).
    """
    key = api_key or BING_API_KEY or os.environ.get("BING_API_KEY", "")
    if not key or key == "your_actual_bing_api_key_here":
        return {
            "error": "Bing API key not provided. Set BING_API_KEY environment variable or pass api_key."
        }
        
    endpoint = f"https://ssl.bing.com/webmaster/api.svc/json/GetQueryStats?siteUrl={site_url}&apikey={key}"
    resp = requests.get(endpoint, timeout=15)
    if resp.status_code != 200:
        return {"error": f"Bing API returned HTTP {resp.status_code}", "detail": resp.text}
    return resp.json()


@mcp.tool
def bing_indexnow_submit(host: str, key: str, url_list: List[str], key_location: Optional[str] = None) -> Dict[str, Any]:
    """
    Instantly submit newly published or updated URLs to Bing and participating search engines via IndexNow.
    
    Args:
        host: Hostname (e.g. 'sipswpcalculator.com').
        key: IndexNow verification key (hosted at https://<host>/<key>.txt).
        url_list: List of full URLs to submit for immediate crawling.
        key_location: Optional custom location URL of the key text file.
    """
    payload = {
        "host": host,
        "key": key,
        "urlList": url_list
    }
    if key_location:
        payload["keyLocation"] = key_location
        
    resp = requests.post("https://api.indexnow.org/IndexNow", json=payload, headers={"Content-Type": "application/json; charset=utf-8"}, timeout=15)
    return {
        "status_code": resp.status_code,
        "success": resp.status_code in [200, 202],
        "message": "URLs submitted successfully to IndexNow" if resp.status_code in [200, 202] else resp.text
    }


@mcp.tool
def ga4_query_report(
    property_id: Optional[str] = None,
    days: int = 28,
    metrics: Optional[List[str]] = None,
    dimensions: Optional[List[str]] = None,
    row_limit: int = 50,
    start_row: int = 0
) -> Dict[str, Any]:
    """
    Query historical traffic, sessions, pageviews, and user engagement metrics from Google Analytics 4 (GA4).
    
    Args:
        property_id: The numeric GA4 Property ID (e.g. '123456789' or 'properties/123456789').
                     Defaults to auto-discovery or configured GA4_PROPERTY_ID.
        days: Number of days back to query (e.g. 7, 28, 90). Defaults to 28 days.
        metrics: Metrics to query (e.g. 'activeUsers', 'sessions', 'screenPageViews', 'averageSessionDuration', 'bounceRate', 'conversions').
                 Defaults to ['activeUsers', 'sessions', 'screenPageViews', 'averageSessionDuration', 'bounceRate'].
        dimensions: Dimensions to group by (e.g. 'pagePath', 'sessionDefaultChannelGroup', 'country', 'deviceCategory', 'date').
                    Defaults to ['pagePath'].
        row_limit: Maximum number of rows to return (up to 10000). Defaults to 50.
        start_row: Zero-based row offset for pagination. Defaults to 0.
    """
    target_property = resolve_ga4_property_id(property_id)
    if not target_property:
        return {
            "error": "GA4 Property ID not provided or discovered.",
            "instructions": (
                "Provide the numeric property_id parameter or set GA4_PROPERTY_ID in environment/.agents/mcp_config.json. "
                "Also ensure the service account (gsc-bot@sipswpcalculator-gsc.iam.gserviceaccount.com) "
                "is added as a Viewer in Google Analytics Admin -> Property Access Management."
            )
        }

    if metrics is None:
        metrics = ["activeUsers", "sessions", "screenPageViews", "averageSessionDuration", "bounceRate"]
    if dimensions is None:
        dimensions = ["pagePath"]

    start_date = f"{days}daysAgo"
    end_date = "yesterday"

    request_body = {
        "dateRanges": [{"startDate": start_date, "endDate": end_date}],
        "dimensions": [{"name": dim} for dim in dimensions],
        "metrics": [{"name": met} for met in metrics],
        "limit": min(row_limit, 10000),
        "offset": start_row
    }

    try:
        service = get_ga4_service()
        response = service.properties().runReport(
            property=f"properties/{target_property}", body=request_body
        ).execute()
    except Exception as e:
        return {
            "error": f"GA4 Data API error: {str(e)}",
            "property_id": target_property,
            "help": (
                "Ensure the service account gsc-bot@sipswpcalculator-gsc.iam.gserviceaccount.com "
                "is granted 'Viewer' role under Google Analytics -> Admin -> Property Access Management."
            )
        }

    dim_headers = [h.get("name", "") for h in response.get("dimensionHeaders", [])]
    metric_headers = [h.get("name", "") for h in response.get("metricHeaders", [])]
    
    formatted_rows = []
    for r in response.get("rows", []):
        row_dims = {dim_headers[i]: v.get("value") for i, v in enumerate(r.get("dimensionValues", []))}
        row_metrics = {}
        for i, v in enumerate(r.get("metricValues", [])):
            val_str = v.get("value", "0")
            try:
                val_num = float(val_str) if "." in val_str else int(val_str)
                if isinstance(val_num, float):
                    val_num = round(val_num, 3)
            except ValueError:
                val_num = val_str
            row_metrics[metric_headers[i]] = val_num
            
        formatted_rows.append({
            "dimensions": row_dims,
            "metrics": row_metrics
        })

    return {
        "property_id": target_property,
        "date_range": f"{start_date} to {end_date}",
        "total_rows": response.get("rowCount", len(formatted_rows)),
        "dimensions": dim_headers,
        "metrics": metric_headers,
        "rows": formatted_rows
    }


@mcp.tool
def ga4_realtime_report(
    property_id: Optional[str] = None,
    metrics: Optional[List[str]] = None,
    dimensions: Optional[List[str]] = None,
    row_limit: int = 25
) -> Dict[str, Any]:
    """
    Query real-time active users and events occurring on the site right now (last 30 minutes).
    
    Args:
        property_id: The numeric GA4 Property ID. Defaults to auto-discovery or configured GA4_PROPERTY_ID.
        metrics: Realtime metrics to query (e.g. 'activeUsers', 'eventCount'). Defaults to ['activeUsers'].
        dimensions: Realtime dimensions to group by (e.g. 'unifiedScreenName', 'pagePath', 'country', 'city', 'deviceCategory').
                    Defaults to ['pagePath'].
        row_limit: Maximum number of rows to return (up to 1000). Defaults to 25.
    """
    target_property = resolve_ga4_property_id(property_id)
    if not target_property:
        return {
            "error": "GA4 Property ID not provided or discovered.",
            "instructions": (
                "Provide the numeric property_id parameter or set GA4_PROPERTY_ID in environment/.agents/mcp_config.json. "
                "Also ensure the service account (gsc-bot@sipswpcalculator-gsc.iam.gserviceaccount.com) "
                "is added as a Viewer in Google Analytics Admin -> Property Access Management."
            )
        }

    if metrics is None:
        metrics = ["activeUsers"]
    if dimensions is None:
        dimensions = ["pagePath"]

    request_body = {
        "dimensions": [{"name": dim} for dim in dimensions],
        "metrics": [{"name": met} for met in metrics],
        "limit": min(row_limit, 1000)
    }

    try:
        service = get_ga4_service()
        response = service.properties().runRealtimeReport(
            property=f"properties/{target_property}", body=request_body
        ).execute()
    except Exception as e:
        return {
            "error": f"GA4 Realtime API error: {str(e)}",
            "property_id": target_property,
            "help": (
                "Ensure the service account gsc-bot@sipswpcalculator-gsc.iam.gserviceaccount.com "
                "is granted 'Viewer' role under Google Analytics -> Admin -> Property Access Management."
            )
        }

    dim_headers = [h.get("name", "") for h in response.get("dimensionHeaders", [])]
    metric_headers = [h.get("name", "") for h in response.get("metricHeaders", [])]
    
    formatted_rows = []
    total_active_users = 0
    for r in response.get("rows", []):
        row_dims = {dim_headers[i]: v.get("value") for i, v in enumerate(r.get("dimensionValues", []))}
        row_metrics = {}
        for i, v in enumerate(r.get("metricValues", [])):
            val_str = v.get("value", "0")
            try:
                val_num = int(val_str) if "." not in val_str else round(float(val_str), 3)
            except ValueError:
                val_num = val_str
            row_metrics[metric_headers[i]] = val_num
            if metric_headers[i] == "activeUsers" and isinstance(val_num, (int, float)):
                total_active_users += int(val_num)
                
        formatted_rows.append({
            "dimensions": row_dims,
            "metrics": row_metrics
        })

    return {
        "property_id": target_property,
        "total_active_users_last_30m": total_active_users,
        "dimensions": dim_headers,
        "metrics": metric_headers,
        "rows": formatted_rows
    }


if __name__ == "__main__":
    mcp.run()
