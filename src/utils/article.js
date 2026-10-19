export function formatArticleHtml(body) {
  const safe = String(body)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')

  return safe
    .split('\n')
    .map((line) => {
      const text = line.trim()
      if (!text) return ''
      if (/^【.+】$/.test(text)) {
        return `<h3 class="art__h">${text.replace(/[【】]/g, '')}</h3>`
      }
      const rich = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
      if (/^(\d+[.、]|[-–])/.test(text)) {
        return `<p class="art__li">${rich}</p>`
      }
      return `<p>${rich}</p>`
    })
    .join('')
}
