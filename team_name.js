// Set your organization or team name here with language variants.
// Example: const TEAM_NAME = { en: 'ACME ', zh: 'ACME ' };
const TEAM_NAME = { en: '', zh: '' };

function applyTeamName() {
  if (typeof TEAM_NAME === 'undefined') return;
  const lang = localStorage.getItem('lang') || document.documentElement.lang || 'zh';
  const name = typeof TEAM_NAME === 'string' ? TEAM_NAME : (TEAM_NAME[lang] || '');
  if (!name) return;
  const regex = /(团队|Team)/g;
  const replacer = (match, p1, offset, str) => {
    return str.slice(Math.max(0, offset - name.length), offset) === name ? match : name + match;
  };
  document.title = document.title.replace(regex, replacer);

  const replaceElementTextNodes = (element) => {
    if (!element) return;
    element.childNodes.forEach((child) => {
      if (child.nodeType === Node.TEXT_NODE) {
        child.textContent = child.textContent.replace(regex, replacer);
      }
    });
  };

  // Only apply to static UI copy that participates in i18n.
  document.querySelectorAll('[data-i18n]').forEach(replaceElementTextNodes);
}

document.addEventListener('DOMContentLoaded', applyTeamName);
