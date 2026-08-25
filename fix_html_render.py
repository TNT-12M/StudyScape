import os

# Fix practice.html
path = 'public/practice.html'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Replace renderBlanks function
old_func = content[content.find('function renderBlanks'):content.find('function renderBlanks')+250]
print('OLD FUNC START:', repr(old_func[:80]))

# Find the exact function
idx = content.find('function renderBlanks(content) {')
end_idx = content.find('function stripOptionLetter', idx)
old_func_full = content[idx:end_idx]
print('OLD FUNC:', repr(old_func_full))

new_func = '''function renderBlanks(content, isHtml) {
    if (!content) return '';
    if (isHtml) {
        return content.replace(/{{\\s*(\\d+)\\s*}}/g,
            (_, n) => <span class="blank-tag">第空</span>);
    }
    return escapeHtml(content).replace(/{{\\s*(\\d+)\\s*}}/g,
        (_, n) => <span class="blank-tag">第空</span>);
}

'''
content = content.replace(old_func_full, new_func)

# 2. Update call site
content = content.replace(
    '<div class="q-content"></div>',
    '<div class="q-content"></div>'
)

# 3. Add CSS
css = '<style>.q-content.q-html img{max-width:100%;height:auto;display:block;margin:.5rem auto}.q-content.q-html table{max-width:100%;overflow-x:auto;display:block}</style>'
if '.q-content.q-html' not in content:
    content = content.replace('</head>', css + '\\n</head>')

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)
print('practice.html updated OK')

# Fix exam.html
path2 = 'public/exam.html'
with open(path2, 'r', encoding='utf-8') as f:
    content2 = f.read()

idx2 = content2.find('function renderBlanks(content) {')
end_idx2 = content2.find('function stripOptionLetter', idx2)
old_func2 = content2[idx2:end_idx2]
print('OLD FUNC2:', repr(old_func2[:100]))

new_func2 = '''function renderBlanks(content, isHtml) {
    if (!content) return '';
    if (isHtml) {
        return content.replace(/{{\\s*(\\d+)\\s*}}/g,
            (_, n) => <span class="blank-tag">第空</span>
        );
    }
    const html = escapeHtml(content);
    return html.replace(/{{\\s*(\\d+)\\s*}}/g,
        (_, n) => <span class="blank-tag">第空</span>
    );
}

'''
content2 = content2.replace(old_func2, new_func2)

# Update both call sites
content2 = content2.replace(
    '<div class="q-content"></div>',
    '<div class="q-content"></div>'
)

if '.q-content.q-html' not in content2:
    content2 = content2.replace('</head>', css + '\\n</head>')

with open(path2, 'w', encoding='utf-8') as f:
    f.write(content2)
print('exam.html updated OK')
