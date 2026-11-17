with open('app/Controllers/AIController.php', 'r', encoding='utf-8', errors='ignore') as fp:
    lines = fp.readlines()

for i, line in enumerate(lines):
    if 'getChatHistory' in line:
        print(f"Line {i+1}: {line.strip()}")
        # print 15 lines before and after
        for j in range(max(0, i-5), min(len(lines), i+25)):
            print(f"  {j+1}: {lines[j].rstrip()}")

