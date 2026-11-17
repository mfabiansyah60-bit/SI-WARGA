with open('app/Helpers/AIDataHelper.php', 'r', encoding='utf-8', errors='ignore') as fp:
    lines = fp.readlines()

print(f"Total lines in AIDataHelper.php: {len(lines)}")

methods = []
for i, line in enumerate(lines):
    if 'function ' in line:
        methods.append((i+1, line.strip()))

for m in methods:
    print(f"Line {m[0]}: {m[1]}")

