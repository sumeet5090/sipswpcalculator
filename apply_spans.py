import os
import glob
import re

php_files = glob.glob('*.php')

def replacer(match):
    span_start = match.group(1) if match.group(1) else ''
    num_str = match.group(2)
    suffix = match.group(3).lower()
    
    num = float(num_str)
    if 'lakh' in suffix:
        amount = num * 100000
    elif 'crore' in suffix:
        amount = num * 10000000
    elif 'million' in suffix:
        amount = num * 1000000
    elif 'billion' in suffix:
        amount = num * 1000000000
    elif 'k' in suffix:
        amount = num * 1000
    else:
        amount = num
        
    # Formatting integer vs float
    if amount == int(amount):
        amount_str = str(int(amount))
    else:
        amount_str = str(amount)
        
    return f'<span class="dynamic-amount" data-amount="{amount_str}"></span>'

pattern1 = r'(<span class="currency-text">\$</span>|\$)\s*([\d\.]+)\s+(Lakhs?|Crores?|Millions?|Billions?)'
# Also matches ₹ if any
pattern2 = r'(<span class="currency-text">₹</span>|₹|Rs\.?)\s*([\d\.]+)\s+(Lakhs?|Crores?|Millions?|Billions?)'

for file in php_files:
    if file in ["navbar.php", "footer.php", "functions.php", "swp-tax-calculator.php"]: 
        # let's include all php files except functions, test_replace, etc
        pass

    if file in ["apply_spans.py", "finalize_replace.php", "test_regex.py", "sync_logos.py"]: continue
        
    with open(file, "r") as f:
        content = f.read()
        
    original = content
    content = re.sub(pattern1, replacer, content, flags=re.IGNORECASE)
    content = re.sub(pattern2, replacer, content, flags=re.IGNORECASE)
    
    # a few exact matches
    content = content.replace('4 Lakh', '<span class="dynamic-amount" data-amount="400000"></span>')
    content = content.replace('1 Cr ', '<span class="dynamic-amount" data-amount="10000000"></span> ')
    content = content.replace('1 Crore', '<span class="dynamic-amount" data-amount="10000000"></span>')
    content = content.replace('31 Lakh', '<span class="dynamic-amount" data-amount="3100000"></span>')
    
    if content != original:
        with open(file, "w") as f:
            f.write(content)
        print(f"Updated {file}")
