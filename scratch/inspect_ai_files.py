import os

files_to_check = [
    'app/AI/RoleCapabilities.php',
    'app/AI/DatabaseInspector.php',
    'app/AI/ScopeFilter.php',
]

for f in files_to_check:
    print(f"=== {f} ===")
    if os.path.exists(f):
        with open(f, 'r', encoding='utf-8', errors='ignore') as fp:
            lines = fp.readlines()
            print(f"Total lines: {len(lines)}")
            print("First 40 lines:")
            print("".join(lines[:40]))
    else:
        print("File does not exist")

