import io
import re
import subprocess
import sys
import tokenize
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
HEADER_CSS = {"style.css"}
problems = []


def flag(path, line, msg):
    problems.append(f"{path}:{line}: {msg}")


def check_php(path, text):
    if "Plugin Name:" in text[:400]:
        text = re.sub(r"/\*.*?\*/", lambda m: "\n" * m.group(0).count("\n"), text, count=1, flags=re.S)
    for i, line in enumerate(text.splitlines(), 1):
        s = line.strip()
        if re.match(r"^(//|#(\s|$)|/\*|\*\s|\*/)", s):
            flag(path, i, "comment")
        if re.search(r"(?<![:\"'])\s//\s", line) and "http" not in line:
            flag(path, i, "inline comment")


def check_css(path, text):
    body = text
    if path.name in HEADER_CSS:
        body = re.sub(r"^/\*.*?\*/", "", text, count=1, flags=re.S)
    if "/*" in body:
        flag(path, 0, "CSS comment")


def check_js(path, text):
    for i, line in enumerate(text.splitlines(), 1):
        if re.search(r"(^|\s)//", line) or re.search(r"(^|\s)/\*", line):
            flag(path, i, "JS comment")
        if "console.log" in line and "tools/test" not in str(path):
            flag(path, i, "console.log")


def check_html(path, text):
    for m in re.finditer(r"<!--(?! /?wp:)", text):
        flag(path, text[: m.start()].count("\n") + 1, "HTML comment")


def check_py(path, text):
    for tok in tokenize.generate_tokens(io.StringIO(text).readline):
        if tok.type == tokenize.COMMENT:
            flag(path, tok.start[0], "Python comment")


def check_yaml(path, text):
    for i, line in enumerate(text.splitlines(), 1):
        if line.strip().startswith("#"):
            flag(path, i, "YAML comment")


def main(paths):
    roots = [Path(p) for p in paths] or [ROOT]
    for root in roots:
        for path in root.rglob("*"):
            if not path.is_file() or any(part in {".git", "node_modules", "dist", ".wp", "shots"} for part in path.parts):
                continue
            ext = path.suffix
            if ext not in {".php", ".css", ".js", ".mjs", ".html", ".py", ".yml", ".yaml", ".json"}:
                continue
            text = path.read_text(errors="ignore")
            if path.resolve() == Path(__file__).resolve():
                continue
            if chr(0x2014) in text:
                flag(path, 0, "em dash")
            if re.search(r"\b" + "TO" + "DO" + r"\b|\bFIX" + "ME" + r"\b", text):
                flag(path, 0, "TODO")
            if ext == ".php":
                check_php(path, text)
                r = subprocess.run(["php", "-l", str(path)], capture_output=True, text=True)
                if r.returncode:
                    flag(path, 0, r.stdout.strip() or r.stderr.strip())
            elif ext == ".css":
                check_css(path, text)
            elif ext in {".js", ".mjs"}:
                check_js(path, text)
            elif ext == ".html":
                check_html(path, text)
            elif ext == ".py":
                check_py(path, text)
            elif ext in {".yml", ".yaml"}:
                check_yaml(path, text)
    for p in problems:
        print(p)
    print(f"{len(problems)} problems")
    return 1 if problems else 0


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:]))
