import os
import re

pattern = re.compile(r'(<\?.*?\?>|<script\b[^>]*>.*?</script>|<style\b[^>]*>.*?</style>|<[^>]+>)', re.IGNORECASE | re.DOTALL)

def process_file(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    parts = pattern.split(content)
    new_parts = []
    
    for i, part in enumerate(parts):
        if i % 2 == 0: # Text nodes
            # Only replace if not immediately preceded by our own span tags
            if i > 0 and ('currency-text' in parts[i-1] or 'currency-symbol' in parts[i-1]):
                pass # skip
            else:
                part = part.replace('$', '<span class="currency-text">$</span>')
                part = part.replace('₹', '<span class="currency-text">₹</span>')
                part = part.replace('£', '<span class="currency-text">£</span>')
                part = part.replace('€', '<span class="currency-text">€</span>')
        new_parts.append(part)
        
    new_content = ''.join(new_parts)
    
    if new_content != content:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"Updated {filepath}")

if __name__ == '__main__':
    for filename in os.listdir('.'):
        if filename.endswith('.php') and not filename.endswith('test_replace.php') and not filename.endswith('finalize_replace.php'):
            process_file(filename)
    print("Done processing files.")
