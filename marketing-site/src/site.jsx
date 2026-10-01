import { useEffect, useState } from 'react';
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import { ArrowDown, ArrowRight, ArrowUpRight, Check, ChevronRight, Compass, Menu, Network, UsersRound, X } from 'lucide-react';
import { Link, Route, Routes, useLocation, useParams } from 'react-router-dom';
import pages from './content.json';

const home = pages.find((page) => page.slug === '');
const articles = pages.filter((page) => page.slug);
const symbols = [Network, UsersRound, Compass];

function Brand() {
  return <Link className="brand" to="/" aria-label="District Team home"><span className="brand-mark"><span /><span /><span /></span><span>district<span className="brand-light">team</span></span></Link>;
}

function Header() {
  const [open, setOpen] = useState(false);
  const location = useLocation();
  useEffect(() => setOpen(false), [location.pathname]);

  return <header className="site-header"><div className="header-inner"><Brand /><button className="menu-button" aria-label={open ? 'Close menu' : 'Open menu'} aria-expanded={open} onClick={() => setOpen(!open)}>{open ? <X /> : <Menu />}</button><nav className={open ? 'main-nav is-open' : 'main-nav'} aria-label="Main navigation"><div className="nav-links"><Link to="/" className={location.pathname === '/' ? 'nav-link active' : 'nav-link'}>Overview</Link>{articles.map((page) => <Link key={page.slug} to={`/${page.slug}`} className={location.pathname === `/${page.slug}` ? 'nav-link active' : 'nav-link'}>{page.navTitle || page.title}</Link>)}</div><a className="nav-cta" href="https://github.com/LBDistrictScouts/DistrictTeam" target="_blank" rel="noreferrer">Explore the project <ArrowUpRight size={15} /></a></nav></div></header>;
}

function Footer() {
  return <footer className="site-footer"><div className="footer-inner"><Brand /><p>Built for people who help local Scouting thrive.</p><a href="https://github.com/LBDistrictScouts/DistrictTeam" target="_blank" rel="noreferrer">Made for the district, in the open <ArrowUpRight size={14} /></a></div></footer>;
}

function VisualCard() {
  return <div className="product-visual" aria-label="Illustration of a district team workspace using sample data"><div className="visual-top"><div className="window-dots"><i /><i /><i /></div><span className="visual-caption">SAMPLE WORKSPACE</span><span className="live-pill"><span /> Connected</span></div><div className="visual-body"><aside className="visual-rail"><div className="rail-symbol">d.</div><span className="rail-active"><Network size={17} /></span><span><UsersRound size={17} /></span><span><Compass size={17} /></span></aside><div className="visual-content"><div className="visual-welcome"><div><div className="mini-label">DISTRICT WORKSPACE</div><h3>Your people, in sync.</h3><p>A little more clarity for the work that matters.</p></div><div className="avatar-stack"><b>JM</b><b>AR</b><b>+8</b></div></div><div className="stat-row"><div className="stat-card"><span>Active teams</span><strong>24</strong><small><span className="stat-up">↗ 3</span> this season</small></div><div className="stat-card"><span>People connected</span><strong>186</strong><small><span className="stat-up">●</span> Across your district</small></div></div><div className="team-card"><div className="team-card-heading"><span>TEAM STRUCTURE</span><span>View all <ArrowUpRight size={13} /></span></div><div className="team-line"><span className="team-icon team-icon-green"><Network size={15} /></span><span><strong>District Leadership</strong><small>District team · 8 roles</small></span><i>08</i></div><div className="team-line"><span className="team-icon team-icon-yellow"><UsersRound size={15} /></span><span><strong>Letchworth Group</strong><small>Group team · 12 roles</small></span><i>12</i></div><div className="team-line"><span className="team-icon team-icon-blue"><Compass size={15} /></span><span><strong>First Letchworth Scouts</strong><small>Section team · 6 roles</small></span><i>06</i></div></div></div></div><div className="visual-note"><span><Check size={14} /> One connected view</span><span>PEOPLE · TEAMS · ROLES</span></div></div>;
}

function HomePage() {
  const highlights = articles.filter((page) => page.featured !== false).slice(0, 3);
  return <>
    <main>
      <section className="hero section-wrap"><div className="hero-copy"><div className="eyebrow"><span className="eyebrow-dot" />{home.eyebrow || 'A workspace for district teams'}</div><h1>{home.title}</h1><p className="hero-description">{home.description}</p><div className="hero-actions"><a className="button button-dark" href="https://github.com/LBDistrictScouts/DistrictTeam" target="_blank" rel="noreferrer">Explore District Team <ArrowUpRight size={17} /></a><a className="text-link" href="#/teams">See how it works <ArrowDown size={16} /></a></div><div className="hero-proof"><div className="proof-avatars"><span>J</span><span>A</span><span>S</span><span>+</span></div><span>Made around the way local Scouting works</span></div></div><VisualCard /><div className="hero-side-note"><span>01</span><span>FOR THE PEOPLE<br />BEHIND THE BADGES</span></div></section>
      <section className="intro-band"><div className="section-wrap intro-inner"><span className="eyebrow">A clearer picture</span><div className="intro-prose"><ReactMarkdown remarkPlugins={[remarkGfm]}>{home.content}</ReactMarkdown></div><span className="intro-mark">✳</span></div></section>
      <section className="features section-wrap" id="features"><div className="section-heading"><div><div className="eyebrow"><span className="eyebrow-dot" />THE WHOLE PICTURE</div><h2>Less chasing details.<br /><span>More room to lead.</span></h2></div><p>Bring team structure, roles and member information together in a workspace that reflects your district.</p></div><div className="feature-grid">{highlights.map((page, index) => { const Icon = symbols[index % symbols.length]; return <Link className="feature-card" key={page.slug} to={`/${page.slug}`}><div className={`feature-icon feature-icon-${index + 1}`}><Icon size={21} strokeWidth={1.8} /></div><span className="feature-number">0{index + 1}</span><h3>{page.cardTitle || page.navTitle || page.title}</h3><p>{page.cardDescription || page.description}</p><span className="feature-link">Explore {page.navTitle || 'feature'} <ArrowRight size={15} /></span></Link>; })}</div></section>
      <section className="quote-band"><div className="quote-inner section-wrap"><span className="quote-mark">✳</span><div><div className="eyebrow">MADE FOR THE REAL WORK</div><blockquote>Clear structure gives people more room to support one another and help young people thrive.</blockquote></div><span className="quote-aside">LOCAL KNOWLEDGE.<br />SHARED PURPOSE.</span></div></section>
      <section className="closing section-wrap"><div className="closing-stamp">DT<span>✳</span></div><div><div className="eyebrow">START WITH A CLEARER VIEW</div><h2>Make the work behind<br />the work <em>feel lighter.</em></h2></div><a className="button button-light" href="https://github.com/LBDistrictScouts/DistrictTeam" target="_blank" rel="noreferrer">Visit the project <ArrowUpRight size={17} /></a></section>
    </main>
  </>;
}

function MarkdownPage() {
  const { slug } = useParams();
  const page = pages.find((item) => item.slug === slug);
  if (!page) return <main className="article-wrap"><div className="eyebrow">PAGE NOT FOUND</div><h1>We couldn’t find that page.</h1><Link className="text-link" to="/">Back to the overview <ArrowRight size={15} /></Link></main>;

  return <main className="article-layout"><aside className="article-aside"><Link className="back-link" to="/"><ArrowRight size={15} className="back-arrow" /> Back to overview</Link><div className="aside-rule" /><span className="aside-label">IN THIS SECTION</span>{articles.map((item) => <Link key={item.slug} className={item.slug === slug ? 'aside-link active' : 'aside-link'} to={`/${item.slug}`}>{item.navTitle || item.title}<ChevronRight size={14} /></Link>)}<div className="aside-note"><span>✳</span><p>Built around the teams who make local Scouting happen.</p></div></aside><article className="article-content"><div className="eyebrow"><span className="eyebrow-dot" />{page.eyebrow || 'DISTRICT TEAM'}</div><h1>{page.title}</h1><p className="article-lede">{page.description}</p><div className="markdown-body"><ReactMarkdown remarkPlugins={[remarkGfm]}>{page.content}</ReactMarkdown></div><div className="article-next"><span>KEEP EXPLORING</span>{articles.filter((item) => item.slug !== slug).map((item) => <Link key={item.slug} to={`/${item.slug}`}>{item.navTitle || item.title}<ArrowRight size={15} /></Link>)}</div></article></main>;
}

export default function App() {
  const location = useLocation();
  useEffect(() => { window.scrollTo(0, 0); }, [location.pathname]);
  return <div className="app-shell"><Header /><Routes><Route path="/" element={<HomePage />} /><Route path="/:slug" element={<MarkdownPage />} /><Route path="*" element={<HomePage />} /></Routes><Footer /></div>;
}
