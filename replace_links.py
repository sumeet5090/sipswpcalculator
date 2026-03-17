import os
import glob
import re

php_files = glob.glob('*.php')

for file in php_files:
    with open(file, "r") as f:
        content = f.read()
    
    original = content
    
    # We want to replace href="/" with href="/#calculator-heading"
    # ONLY for anchor tags <a>.
    
    content = re.sub(r'<a([^>]+)href="/"', r'<a\1href="/#calculator-heading"', content)
    content = re.sub(r'<a([^>]+)href="https://sipswpcalculator\.com/"', r'<a\1href="https://sipswpcalculator.com/#calculator-heading"', content)
    
    # Sometimes href is before other attributes:
    # <a href="/" class="...">
    content = re.sub(r'<a\s+href="/"([^>]*)>', r'<a href="/#calculator-heading"\1>', content)
    content = re.sub(r'<a\s+href="https://sipswpcalculator\.com/"([^>]*)>', r'<a href="https://sipswpcalculator.com/#calculator-heading"\1>', content)
    
    if content != original:
        with open(file, "w") as f:
            f.write(content)
        print(f"Updated {file}")
