import os

def fix_file(path):
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Fix broken template literals: "第空" -> "第${n}空" with backticks
    # Pattern: (_, n) => <span class="blank-tag">第空</span>
    # Replace with: (_, n) => `<span class="blank-tag">第${n}空</span>`
    import re
    
    # The broken pattern: arrow followed by space, then span with 第空
    old = r'(_, n) => <span class="blank-tag">第空</span>'
    new = r'(_, n) => `<span class="blank-tag">第${n}空</span>`'
    
    if re.search(old, content):
        content = re.sub(old, new, content)
        print(f'Fixed template literals in {path}')
    else:
        print(f'Pattern not found in {path}')
        # Debug: find the function
        idx = content.find('function renderBlanks(content, isHtml)')
        if idx >= 0:
            print(f'  Found renderBlanks at {idx}')
            snippet = content[idx:idx+300]
            print(f'  Snippet: {repr(snippet[:200])}')
    
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)

fix_file('public/practice.html')
fix_file('public/exam.html')
