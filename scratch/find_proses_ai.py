import re

with open('app/Controllers/AIController.php', 'r', encoding='utf-8', errors='ignore') as f:
    content = f.read()

# find function prosesAI
match = re.search(r'function prosesAI\b', content)
if match:
    start = match.start()
    print("Found prosesAI at character", start)
    # print 1500 chars around it
    print(content[start:start+2500])
else:
    print("prosesAI function definition not found directly, searching for 'prosesAI' occurrences:")
    for m in re.finditer(r'prosesAI', content):
        line_num = content[:m.start()].count('\n') + 1
        print(f"Line {line_num}: {content[m.start()-50:m.end()+50].strip()}")

