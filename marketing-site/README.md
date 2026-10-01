# District Team marketing site

This React site turns the Markdown pages in the repository's `/docs` folder into a small product marketing site. The content build reads each Markdown file's frontmatter and body before Vite creates the deployable site.

Run it locally from this folder:

```sh
corepack yarn install
corepack yarn dev
```

To add a page, create a `.md` file in `/docs` with `title` and `description` frontmatter. `navTitle`, `eyebrow`, `order`, `cardTitle`, and `cardDescription` are optional. The homepage is `docs/index.md`; other Markdown files become linked feature pages. The Pages workflow deploys when `/docs`, `/marketing-site`, or the workflow itself changes on `main`, and it can also be started manually.

Build the static site with:

```sh
corepack yarn build
```
