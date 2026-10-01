import fs from 'node:fs/promises';
import path from 'node:path';
import matter from 'gray-matter';

const docsDir = path.resolve(import.meta.dirname, '../../docs');
const outputFile = path.resolve(import.meta.dirname, '../src/content.json');
const files = (await fs.readdir(docsDir))
  .filter((file) => file.endsWith('.md'))
  .sort((left, right) => left.localeCompare(right));

const pages = await Promise.all(files.map(async (file) => {
  const raw = await fs.readFile(path.join(docsDir, file), 'utf8');
  const { data, content } = matter(raw);
  const slug = file === 'index.md' ? '' : file.replace(/\.md$/, '');

  if (!data.title || !data.description) {
    throw new Error(`${file} must define title and description in its frontmatter.`);
  }

  return {
    ...data,
    slug,
    content: content.trim(),
  };
}));

if (!pages.some((page) => page.slug === '')) {
  throw new Error('docs/index.md is required to build the marketing homepage.');
}

pages.sort((left, right) => (left.order ?? 999) - (right.order ?? 999));
await fs.writeFile(outputFile, `${JSON.stringify(pages, null, 2)}\n`);
