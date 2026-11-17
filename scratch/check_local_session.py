import re

with open('app/Controllers/AIController.php', 'r', encoding='utf-8', errors='ignore') as fp:

    content = fp.read()

# search for uses of $sessionId inside prosesAILokal
p_local_start = content.find('function prosesAILokal(')
p_local_end = content.find('function tanyaGeminiFailover(')

p_local = content[p_local_start:p_local_end]

print("Length of prosesAILokal:", len(p_local))
occurrences = [m.start() for m in re.finditer(r'\$sessionId', p_local)]
print("Occurrences of $sessionId in prosesAILokal:", len(occurrences))
for o in occurrences[:10]:
    line_start = p_local.rfind('\n', 0, o)
    line_end = p_local.find('\n', o)
    print("  ->", p_local[line_start:line_end].strip())
