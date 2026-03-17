import os

files_to_fix = [
    "dollar-cost-averaging-tool.php",
    "retirement-drawdown-planner.php",
    "recurring-investment-calculator.php",
    "index.php"
]

for filename in files_to_fix:
    if not os.path.exists(filename):
        continue
        
    with open(filename, "r") as f:
        content = f.read()
    
    original = content
    
    # 1. Clean up potential double style="top: 120px;"
    content = content.replace('style="top: 120px;" style="top: 120px;"', 'style="top: 120px;"')
    
    # 2. Clean up potential double space in section tag
    content = content.replace('style="scroll-margin-top: 120px;"  aria-labelledby', 'style="scroll-margin-top: 120px;" aria-labelledby')

    if content != original:
        with open(filename, "w") as f:
            f.write(content)
        print(f"Cleaned up {filename}")
    else:
        print(f"No cleanup needed for {filename}")
