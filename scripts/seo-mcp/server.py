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


def get_gsc_service():
    if not os.path.exists(GSC_KEY_PATH):
        raise FileNotFoundError(f"GSC credentials file not found at {GSC_KEY_PATH}")
    
    scopes = ["https://www.googleapis.com/auth/webmasters.readonly"]
    creds = service_account.Credentials.from_service_account_file(
        GSC_KEY_PATH, scopes=scopes
    )
    return build("searchconsole", "v1", credentials=creds)


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


if __name__ == "__main__":
    mcp.run()
