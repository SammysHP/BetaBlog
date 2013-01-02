// from http://stackoverflow.com/questions/3964710/replacing-selected-text-in-the-textarea
function getInputSelection(el) {
    if (el == null) {
        return {
            start: 0,
            end: 0
        };
    }
    var start = 0, end = 0, normalizedValue, range,
        textInputRange, len, endRange;
    if (typeof el.selectionStart == "number" && typeof el.selectionEnd == "number") {
        start = el.selectionStart;
        end = el.selectionEnd;
    } else {
        range = document.selection.createRange();
        if (range && range.parentElement() == el) {
            len = el.value.length;
            normalizedValue = el.value.replace(/\r\n/g, "\n");
            textInputRange = el.createTextRange();
            textInputRange.moveToBookmark(range.getBookmark());
            endRange = el.createTextRange();
            endRange.collapse(false);

            if (textInputRange.compareEndPoints("StartToEnd", endRange) > -1) {
                start = end = len;
            } else {
                start = -textInputRange.moveStart("character", -len);
                start += normalizedValue.slice(0, start).split("\n").length - 1;

                if (textInputRange.compareEndPoints("EndToEnd", endRange) > -1) {
                    end = len;
                } else {
                    end = -textInputRange.moveEnd("character", -len);
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

// from http://stackoverflow.com/questions/512528/set-cursor-position-in-html-textbox
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

function simpleEscape(text) {
    return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function simpleUnescape(text) {
    return String(text).replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&');
}

function surroundSelectedText(el, before, after) {
var sel = getInputSelection(el)
var val = el.value;
var text = val.slice(sel.start, sel.end);
el.value = val.slice(0, sel.start) + before + text + after + val.slice(sel.end);
setCaretPosition(el, sel.start + before.length + text.length + after.length);
return false;
}

function escapeSelectedText(el) {
    var sel = getInputSelection(el)
    var val = el.value;
    var text = simpleEscape(val.slice(sel.start, sel.end));
    el.value = val.slice(0, sel.start) + text + val.slice(sel.end);
    setCaretPosition(el, sel.start + text.length);
    return false;
}

function unescapeSelectedText(el) {
    var sel = getInputSelection(el)
    var val = el.value;
    var text = simpleUnescape(val.slice(sel.start, sel.end));
    el.value = val.slice(0, sel.start) + text + val.slice(sel.end);
    setCaretPosition(el, sel.start + text.length);
    return false;
}

function replaceSelectedText(el, text) {
    var sel = getInputSelection(el);
    var val = el.value;
    el.value = val.slice(0, sel.start) + text + val.slice(sel.end);
    setCaretPosition(el, sel.start + text.length);
    return false;
}

function makeBold(el) {
    surroundSelectedText(el, '<b>', '</b>');
    return false;
}

function makeItalic(el) {
    surroundSelectedText(el, '<i>', '</i>');
    return false;
}

function makeUnderline(el) {
    surroundSelectedText(el, '<u>', '</u>');
    return false;
}

function insertBreak(el) {
    surroundSelectedText(el, '<br />\n', '');
    return false;
}

function insertParagraph(el) {
    surroundSelectedText(el, '<p>\n', '\n</p>');
    return false;
}

function makeAnchor(el) {
    surroundSelectedText(el, '<a href="">', '</a>');
    return false;
}

function makeImage(el) {
    surroundSelectedText(el, '<img src="', '" />');
    return false;
}

function makeHeading(el) {
    surroundSelectedText(el, '<h3>', '</h3>');
    return false;
}

function makeCode(el) {
    surroundSelectedText(el, '<code class="no-highlight">', '</code>');
    return false;
}

function makeCodeBlock(el) {
    surroundSelectedText(el, '<pre><code class="no-highlight">', '</code></pre>');
    return false;
}

function makeImageBox(el) {
    var sel = getInputSelection(el)
    var val = el.value;
    var text = val.slice(sel.start, sel.end);
    var newtext = '<a href="' + text + '" class="imagebox">\n<img src="' + text + '" />\n</a>';
    el.value = val.slice(0, sel.start) + newtext + val.slice(sel.end);
    setCaretPosition(el, sel.start + newtext.length);
    return false;
}

function $$$(id) {
    return document.getElementById(id);
}

function insertTag(tag) {
    var el = document.getElementById('taginput');
    
    if (el.value.length == 0) {
        el.value += tag;
    } else {
        el.value += ', ' + tag;
    }
    return false;
}
