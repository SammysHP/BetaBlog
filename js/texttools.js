/**
 * Get the selection range of an element.
 *
 * Requires a non-jQuery object, so use $(el)[0] in conjunction with jQuery.
 *
 * @source http://stackoverflow.com/questions/3964710/replacing-selected-text-in-the-textarea
 * @return {start, end} with start <= end
 */
function getInputSelection(el) {
    if (el == null) {
        return {
            start: 0,
            end: 0
        };
    }

    var start = 0;
    var end = 0;

    if (typeof el.selectionStart == 'number' && typeof el.selectionEnd == 'number') {
        start = el.selectionStart;
        end = el.selectionEnd;
    } else {
        var range = document.selection.createRange();
        if (range && range.parentElement() == el) {
            var len = el.value.length;
            var normalizedValue = el.value.replace(/\r\n/g, "\n");
            var textInputRange = el.createTextRange();
            textInputRange.moveToBookmark(range.getBookmark());
            var endRange = el.createTextRange();
            endRange.collapse(false);

            if (textInputRange.compareEndPoints('StartToEnd', endRange) > -1) {
                start = end = len;
            } else {
                start = -textInputRange.moveStart('character', -len);
                start += normalizedValue.slice(0, start).split("\n").length - 1;

                if (textInputRange.compareEndPoints('EndToEnd', endRange) > -1) {
                    end = len;
                } else {
                    end = -textInputRange.moveEnd('character', -len);
                    end += normalizedValue.slice(0, end).split("\n").length - 1;
                }
            }
        }
    }

    return {
        start: start,
        end: end
    };
}

/**
 * Set the caret position in input or textarea.
 *
 * Requires a non-jQuery object, so use $(el)[0] in conjunction with jQuery.
 *
 * @source http://stackoverflow.com/questions/512528/set-cursor-position-in-html-textbox
 */
function setCaretPosition(el, pos) {
    if (el == null) {
        return false;
    }

    if (el.createTextRange) {
        var range = el.createTextRange();
        range.move('character', pos);
        range.select();
    } else {
        if (el.selectionStart) {
            el.focus();
            el.setSelectionRange(pos, pos);
        } else {
            el.focus();
        }
    }
}

function simpleHtmlEscape(text) {
    return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function simpleHtmlUnescape(text) {
    return String(text).replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&');
}

function surroundSelectedText(el, before, after) {
    var sel = getInputSelection(el)
    var val = el.value;
    var text = val.slice(sel.start, sel.end);
    var scrollposition = el.scrollTop;
    el.value = val.slice(0, sel.start) + before + text + after + val.slice(sel.end);
    el.scrollTop = scrollposition;
    setCaretPosition(el, sel.start + before.length + text.length);
}

function escapeSelectedText(el) {
    var sel = getInputSelection(el)
    var val = el.value;
    var text = simpleHtmlEscape(val.slice(sel.start, sel.end));
    var scrollposition = el.scrollTop;
    el.value = val.slice(0, sel.start) + text + val.slice(sel.end);
    el.scrollTop = scrollposition;
    setCaretPosition(el, sel.start + text.length);
}

function unescapeSelectedText(el) {
    var sel = getInputSelection(el)
    var val = el.value;
    var text = simpleHtmlUnescape(val.slice(sel.start, sel.end));
    var scrollposition = el.scrollTop;
    el.value = val.slice(0, sel.start) + text + val.slice(sel.end);
    el.scrollTop = scrollposition;
    setCaretPosition(el, sel.start + text.length);
}

function replaceSelectedText(el, text) {
    var sel = getInputSelection(el);
    var val = el.value;
    var scrollposition = el.scrollTop;
    el.value = val.slice(0, sel.start) + text + val.slice(sel.end);
    el.scrollTop = scrollposition;
    setCaretPosition(el, sel.start + text.length);
}

function makeBold(el) {
    surroundSelectedText(el, '<b>', '</b>');
}

function makeItalic(el) {
    surroundSelectedText(el, '<i>', '</i>');
}

function makeUnderline(el) {
    surroundSelectedText(el, '<u>', '</u>');
}

function makeBreak(el) {
    surroundSelectedText(el, '<br />\n', '');
}

function makeParagraph(el) {
    surroundSelectedText(el, '<p>\n', '\n</p>');
}

function makeBlockquote(el) {
    surroundSelectedText(el, '<blockquote cite=""><p>\n', '\n</p></blockquote>');
}

function makeQuoteInsert(el) {
    surroundSelectedText(el, '<span class="quoteinsert">', '</span>');
}

function makeLink(el) {
    surroundSelectedText(el, '<a href="">', '</a>');
}

function makeImage(el) {
    surroundSelectedText(el, '<img src="', '" />');
}

function makeHeading(el) {
    surroundSelectedText(el, '<h3>', '</h3>');
}

function makeUnorderedList(el) {
    surroundSelectedText(el, '<ul>\n', '\n</ul>');
}

function makeOrderedList(el) {
    surroundSelectedText(el, '<ol>\n', '\n</ol>');
}

function makeListItem(el) {
    surroundSelectedText(el, '<li>', '</li>');
}

function makeDel(el) {
    surroundSelectedText(el, '<del>', '</del>');
}

function makeCode(el) {
    surroundSelectedText(el, '<code class="no-highlight">', '</code>');
}

function makeCodeBlock(el) {
    surroundSelectedText(el, '<pre><code class="no-highlight">', '</code></pre>');
}

function makeImageBox(el) {
    var sel = getInputSelection(el)
    var val = el.value;
    var text = val.slice(sel.start, sel.end);
    var newtext = '<a href="' + text + '" class="imagebox">\n<img src="' + text + '" />\n</a>';
    var scrollposition = el.scrollTop;
    el.value = val.slice(0, sel.start) + newtext + val.slice(sel.end);
    el.scrollTop = scrollposition;
    setCaretPosition(el, sel.start + newtext.length);
}

function toggleMonospace(el, btn) {
    var element = $(el);
    var button = $(btn);

    if (element.hasClass('monospace')) {
        element.removeClass('monospace');
        button.removeClass('active').blur();
    } else {
        element.addClass('monospace');
        button.addClass('active').blur();
    }
}

function insertTag(tag) {
    var el = document.getElementById('taginput');
    
    if (el.value.length == 0) {
        el.value += tag;
    } else {
        el.value += ', ' + tag;
    }
}

function updatePreview() {
    var container = $('#preview');
    container.html('<div class="adminbox"><a href="#" onClick="hidePreview(); return false;" class="button">ausblenden</a></div><h2>' + $('#title').val() + '</h2>' + $('#content').val() + $('#extended').val() + '<div style="clear: both;">&nbsp;</div>');
    $('code', container).each(function(i, e) {hljs.highlightBlock(e)});
    container.show();
}

function hidePreview() {
    var container = $('#preview');
    container.html('');
    container.hide();
}
