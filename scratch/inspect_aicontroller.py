with open('app/Controllers/AIController.php', 'r', encoding='utf-8', errors='ignore') as fp:
    lines = fp.readlines()

print(f"Total lines in AIController.php: {len(lines)}")

functions = []
for i, line in enumerate(lines):
    if line.strip().startswith('function '):
        functions.append((i+1, line.strip()))

print("\n--- Functions in AIController.php ---")
for f in functions:
    print(f"Line {f[0]}: {f[1]}")

