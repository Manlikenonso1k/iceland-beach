# CODEBASE MAPPING PLAYBOOK

> **Portable. Contains no facts about any specific project.**
> Copy this file into any repository. Follow it once to produce that repo's
> `PROJECT_MAP.md`, then read the map — not this file — at the start of every session.

**What this is for:** a new agent session normally burns a large share of its context
rediscovering a codebase by opening files one at a time. Doing that once, writing the result
down, and reading *that* instead is the cheaper path. This playbook is the procedure for
producing that write-up, plus the traps that make a naive attempt useless.

**Two files, two jobs:**

| File | Scope | Lifetime |
|---|---|---|
| `INSTRUCTIONS.md` (this) | *How* to map any repo | Copied between projects unchanged |
| `PROJECT_MAP.md` | *What* this repo actually is | Rewritten when the repo changes |

---

## PART 1 — SESSION START (the common case)

If `PROJECT_MAP.md` already exists, you are not mapping. Do this instead:

```
Read PROJECT_MAP.md before touching anything.
Use graphify query/explain/path to look things up instead of reading files.
```

Then open only the files the task actually touches. Do not re-read the tree.

Rebuild the map (Part 2) only when the repo has drifted materially — new subsystem, restructure,
or the map is contradicted by what you find. A stale map is worse than none, so when you notice a
fact in `PROJECT_MAP.md` that is now wrong, **fix that line as part of your change**.

---

## PART 2 — MAPPING A REPO FROM SCRATCH

### Step 0 — Install the tool

```bash
graphify --version 2>/dev/null || echo "NOT INSTALLED"
uv tool install graphifyy        # or: pipx install graphifyy / pip install graphifyy
graphify install                 # registers the /graphify skill with your agent
```

Notes worth knowing before you start:
- The install directory may not be on `PATH`. If the binary is missing after a clean install,
  prepend the tool bin dir for the session rather than debugging the shell profile.
- `graphify install` writes a **global** agent config file in your home directory, affecting every
  project. Mention this to the user; don't let it be a surprise.
- A newly installed skill is not available to the *current* session — it registers for the next one.
  Use the CLI directly: `graphify update <path>` builds a graph with **no LLM and no API key**.

### Step 1 — Measure signal before you build ⚠️

**This is the step everyone skips, and it is the one that decides whether the map is worth having.**

Graphify honours `.gitignore`, so dependency directories (`node_modules`, `vendor`, `.venv`,
`target`) are skipped automatically. It does **not** know that some *tracked* directories are not
application code. Modern repos commonly commit thousands of files of agent tooling, generated
bundles, fixtures, or vendored snapshots:

```
.claude/  .agents/  .cursor/  .github/skills/  .devcontainer/
dist/  build/  public/build/  static/  coverage/  docs/_build/
**/__snapshots__/  **/fixtures/  **/testdata/  vendored/  third_party/
```

Count before committing to a build:

```bash
git ls-files | wc -l                                    # everything tracked
git ls-files | grep -cE '\.(php|py|rb|go|rs|java|kt|ts|tsx|js|jsx)$'   # plausible source
for d in .claude .agents .cursor .github dist build public/build; do
  printf '%-16s %s\n' "$d" "$(git ls-files "$d" | wc -l)"
done
```

If the non-source directories hold a comparable number of files to your source, a root-level build
will drown the signal. **Do not skip to Step 2 on faith — build, then verify the ratio.**

### Step 2 — Build, then check what you actually got

```bash
graphify update .
```

Immediately audit where the nodes came from:

```bash
python - <<'PY'
import json, collections
g = json.load(open('graphify-out/graph.json', encoding='utf-8'))
c = collections.Counter()
for n in g['nodes']:
    f = str(n.get('source_file') or '').replace(chr(92), '/')
    c[f.split('/')[0] if f else '(none)'] += 1
total = len(g['nodes'])
print(f"{total} nodes, {len(g.get('links', []))} edges")
for k, v in c.most_common(12):
    print(f'  {v:7d}  {100*v/total:5.1f}%  {k}')
PY
```

Judge it on one number: **what fraction of nodes sit under your source directories?**

- **> 60% source** — good, keep it. Go to Step 4.
- **< 40% source** — the graph is mostly noise. Go to Step 3.

Also sanity-check the artefact sizes. A `graph.json` in the tens of megabytes is a red flag on
both counts: it will not fit comfortably in git, and it usually means noise.

### Step 3 — Scoped rebuild (when the root build is noisy)

You cannot always fix this with `.gitignore`, because the noisy directories are often *legitimately
tracked* — deleting or ignoring them is not yours to decide. Build against a staged copy instead:

```bash
STG=$(mktemp -d)
# Copy ONLY real application source. Adjust to the stack:
#   Laravel : app routes database config tests bootstrap resources/js resources/views
#   Django  : <project> apps manage.py settings tests
#   Rails   : app lib config db spec
#   Node/TS : src lib server test
#   Go      : cmd internal pkg
#   Python  : src <package> tests
cp -r <source-dirs> "$STG"/
cp <manifest files: composer.json package.json pyproject.toml go.mod …> "$STG"/ 2>/dev/null

cd "$STG" && graphify update .
cp graphify-out/{graph.json,GRAPH_REPORT.md,graph.html,manifest.json} <repo>/graphify-out/
```

Mirror the real directory layout in the staging dir so `source_file` paths stay valid
repo-relative paths. Re-run the Step 2 audit to confirm the ratio improved.

**Record the exact staging command in `PROJECT_MAP.md`.** Otherwise the next person runs
`graphify update .` at the root and reintroduces the noise.

### Step 4 — Interrogate the graph

```bash
graphify query "what are the main entry points"
graphify query "what are the main data models and how do they relate"
graphify query "what are the payment/auth/background-job flows"   # whatever this repo does
graphify query "what are the most connected god nodes"
graphify explain "<CoreClass>"
graphify path "<EntryPoint>" "<SideEffect>"
```

Know what these actually return:
- `query` is a **BFS locator**, not a prose answer. It gives ranked nodes with `file:line`.
  Treat it as "where do I look", then read those files.
- `explain` gives a node's degree and every inbound/outbound edge. **This is the most valuable
  command** — high degree identifies the chokepoints that matter.
- `explain` fails on ambiguous labels (a name appearing in a model, a test, and a component).
  Pass the repo-relative path or the node id it offers.
- `query` truncates to a token budget; raise it with `--budget` or narrow the question.

Start from the `Community Hubs` list in `GRAPH_REPORT.md` — those are the highest-degree nodes and
they tell you what the codebase is organised around, which is often not what the directory names suggest.

### Step 5 — Verify against the running app ⚠️

**The graph shows structure, not truth.** It cannot tell you that a config value is wrong, that a
production schema differs from the migrations, or that a documented step no longer exists. Nor can
any pre-existing notes, which are frequently stale.

Confirm the load-bearing facts by running the app, not by reading about it:

```bash
# schema as it really is — not as the migrations claim
<orm/db introspection command>
# routes as registered — not as the route file reads
<route listing command>
# roles/permissions/config as seeded
<framework console one-liner>
# what the deploy script really does
grep -nE '^(#|[a-z])' <deploy script>
# env var NAMES only, never values
grep -oE '^[A-Z_]+=' .env | tr -d '='
```

Every claim you cannot verify should be marked as unverified in the map, or left out. **Never copy
an assertion from an older document into the new map without checking it** — inherited errors are
the main way these files rot.

### Step 6 — Write `PROJECT_MAP.md`

Use this structure. Drop sections that do not apply; do not invent ones that do.

```markdown
# PROJECT MAP — <name>
> Last verified: <date> (commit <sha>). Facts checked against the running app.

## OVERVIEW           what it does, who uses it, distinct product lines, tech-stack table
## DIRECTORY STRUCTURE annotated tree — one line per dir saying what lives there and why
## DATA MODEL          every table/collection: key columns, relationships, notes
## CORE FLOWS          numbered step-by-step for each critical path, entry point → side effect
## ROUTES / API        method, path, handler, name, middleware
## EXTERNAL SERVICES   per integration: env var NAMES, entry points, callbacks, units/formats
## ROLES & PERMISSIONS who can do what, and where it is enforced
## BACKGROUND WORK     jobs, schedules, queues, webhooks
## GOTCHAS ⚠️          the highest-value section — see below
## DEPLOYMENT          pipeline, script steps, host constraints
## HOW TO USE THIS MAP query/explain/path examples + the exact scoped-rebuild command
## GRAPH SUMMARY       node/edge counts, community hubs, the chokepoint classes
```

**Rules that keep it useful:**

- **Size is the point.** Keep it under ~400 lines. **Never paste the graph report into it** — a
  400 KB map costs more to read than the files it replaces, which defeats the entire exercise.
  Link to `graphify-out/GRAPH_REPORT.md` instead.
- **State facts, not impressions.** "`X` has degree 16 and is imported by A, B, C" beats "X is important".
- **Point at `file:line`** so the reader can jump straight there.
- **Write the gotchas as instructions, with the reason.** "Never remove `$timestamps = false` on
  this model — production's table has no such column and every write 500s" is useful. "Careful with
  timestamps" is not. Each gotcha should carry: what to do, what breaks otherwise, and where.
- **Record the non-obvious only.** Anything an agent can read off the code in seconds does not
  belong. Environment/production divergence, deliberate duplication, legacy no-go zones, and
  units/encodings *do*.

**Harvest gotchas from history, not just from the code:**

```bash
git log --oneline -60 | grep -iE 'fix|hotfix|revert|crash|broke|500'
```

Bug-fix commits are where the sharp edges are recorded. A fix that had to be made twice is a
gotcha that will be needed a third time.

### Step 7 — Commit

```bash
cat >> .gitignore <<'EOF'

# Graphify: graph.json + GRAPH_REPORT.md are the committed map. The rest is
# large, regenerable, or machine-local — see INSTRUCTIONS.md to rebuild.
graphify-out/graph.html
graphify-out/cache/
graphify-out/manifest.json
graphify-out/.graphify_*
EOF

git add INSTRUCTIONS.md PROJECT_MAP.md .gitignore \
        graphify-out/graph.json graphify-out/GRAPH_REPORT.md
git commit -m "docs: add codebase map"
```

Commit `graph.json` **only if it is a few MB at most**, so a fresh clone can query without
rebuilding. If the scoped build is still large, gitignore it too and rely on
`GRAPH_REPORT.md` plus the documented rebuild command.

### Step 8 — Prove the map works

Ask questions whose answers you can check:

```bash
graphify query "which files write to <core table>"
graphify query "what happens step by step when <core action> fails"
graphify query "if I added a new <integration>, what would I touch"
```

The map passes if a reader can answer those from `PROJECT_MAP.md` plus the returned `file:line`
locations, without opening the tree. If a question needs a file the map never mentions, that gap
belongs in the map.

---

## THE FIVE FAILURE MODES

Ranked by how much time they cost when missed.

1. **Building at the repo root without checking the signal ratio.** Tracked non-source directories
   can outnumber application code by an order of magnitude, producing a graph that is mostly
   tooling and a `graph.json` too large to commit. Always audit (Step 2).
2. **Pasting the graph report into the map.** Turns a token-saving artefact into a token-burning
   one. Link, never inline.
3. **Copying claims from an existing doc or template.** Stale assertions are worse than silence
   because they are trusted. Verify every fact against the running app (Step 5).
4. **Expecting prose from `query`.** It is a locator. Use it to find, then read.
5. **Writing structure the code already makes obvious, and omitting what it doesn't.** The value is
   concentrated in environment divergence, deliberate duplication, units, and no-go zones.

---

## MAINTENANCE

- After a material change, rerun the **scoped** build recorded in `PROJECT_MAP.md`, then revise the
  affected sections and the `Last verified` line.
- Treat a wrong line in the map like a failing test: fix it in the same change that revealed it.
- If `PROJECT_MAP.md` and the code disagree, **the code wins** — then correct the map.

---
*Portable playbook. Copy into any repo; it names no project by design.*
