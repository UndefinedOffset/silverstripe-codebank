/**
 * SyntaxHighlighter
 * http://alexgorbatchev.com/SyntaxHighlighter
 *
 * SyntaxHighlighter is donationware. If you are using it, please donate.
 * http://alexgorbatchev.com/SyntaxHighlighter/donate.html
 *
 * @version
 * 3.0.83 (July 02 2010)
 * 
 * @copyright
 * Copyright (C) 2004-2010 Alex Gorbatchev.
 *
 * @license
 * Dual licensed under the MIT and GPL licenses.
 * 
 * Modified from shBrushXML.js by SilverStripe Ltd.
 */
;(function()
{
    // CommonJS
    typeof(require) != 'undefined' ? SyntaxHighlighter = require('shCore').SyntaxHighlighter : null;

    function Brush()
    {
        var keywords = "control if end_if else elseif end_control loop end_loop with end_with";
        
        function process(match, regexInfo)
        {
            var constructor = SyntaxHighlighter.Match,
                code = match[0],
                tag = new XRegExp('(&lt;|<)[\\s\\/\\?]*(?<name>[:\\w-\\.]+)', 'xg').exec(code),
                result = []
                ;
        
            if (match.attributes != null) 
            {
                var attributes,
                    regex = new XRegExp('(?<name> [\\w:\\-\\.]+)' +
                                        '\\s*=\\s*' +
                                        '(?<value> ".*?"|\'.*?\'|\\w+)',
                                        'xg');

                while ((attributes = regex.exec(code)) != null) 
                {
                    result.push(new constructor(attributes.name, match.index + attributes.index, 'color1'));
                    result.push(new constructor(attributes.value, match.index + attributes.index + attributes[0].indexOf(attributes.value), 'string'));
                }
            }

            if (tag != null)
                result.push(
                    new constructor(tag.name, match.index + tag[0].indexOf(tag.name), 'keyword')
                );

            return result;
        }
    
        this.regexList = [
            { regex: new XRegExp('(\\&lt;|<)\\!\\[[\\w\\s]*?\\[(.|\\s)*?\\]\\](\\&gt;|>)', 'gm'), css: 'color2' },  // <![ ... [ ... ]]>
            { regex: SyntaxHighlighter.regexLib.xmlComments, css: 'comments' }, // <!-- ... -->
            { regex: /(&lt;|<)%--[\s\S]*?--%(&gt;|>)/gm, css: 'comments' }, // <%-- ... --%>
            { regex: /\$[\w]*/gm, css: 'color2' },  // $Var
            { regex: new XRegExp('(&lt;|<)[\\s\\/\\?]*(\\w+)(?<attributes>.*?)[\\s\\/\\?]*(&gt;|>)', 'sg'), func: process },
            { regex: new RegExp(this.getKeywords(keywords), 'gm'), css: 'keyword' }         // keywords
        ];
    };

    Brush.prototype = new SyntaxHighlighter.Highlighter();
    Brush.aliases   = ['ss', 'silverstripe'];

    SyntaxHighlighter.brushes.SS = Brush;

    // CommonJS
    typeof(exports) != 'undefined' ? exports.Brush = Brush : null;
})();