import marked from 'marked'
import DOMPurify from 'dompurify'

export default function (md = '', options) {
    return DOMPurify.sanitize(marked(md), options)
}