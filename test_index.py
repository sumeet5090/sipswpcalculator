import re

with open("index.php", "r") as f:
    text = f.read()

def replacer_L(m):
    num = float(m.group(1))
    amount = int(num * 100000)
    return f'<span class="dynamic-amount" data-amount="{amount}"></span></span>'

def replacer_Cr(m):
    num = float(m.group(1))
    amount = int(num * 10000000)
    return f'<span class="dynamic-amount" data-amount="{amount}"></span></span>'

def replacer_comma(m):
    num_str = m.group(1).replace(",", "")
    amount = int(num_str)
    return f'<span class="dynamic-amount" data-amount="{amount}"></span></span>'

original = text
text = re.sub(r'<span class="currency-text">\$</span>([0-9\.]+)L</span>', replacer_L, text)
text = re.sub(r'<span class="currency-text">\$</span>([0-9\.]+)Cr</span>', replacer_Cr, text)
text = re.sub(r'<span class="currency-text">\$</span>([0-9,]+)</span>', replacer_comma, text)

# For "sip-calculator.php" we had:
# <span class="currency-text">$</span><span class="dynamic-amount" data-amount="9991479"></span></span>
with open("sip-calculator.php", "r") as f:
    sip_text = f.read()
sip_text = sip_text.replace('<span class="currency-text">$</span><span class="dynamic-amount"', '<span class="dynamic-amount"')    

if text != original:
    with open("index.php", "w") as f:
        f.write(text)
    print("index.php updated")
else:
    print("No changes in index.php")

with open("sip-calculator.php", "w") as f:
    f.write(sip_text)
print("sip-calculator.php updated")
