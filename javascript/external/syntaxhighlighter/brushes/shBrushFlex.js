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
 */
;(function()
{
    var primaryKeywords =   'dynamic extends implements import new case do while else if for ' +
        'in switch throw intrinsic private public static get set try catch finally ' +
        'while with default break continue delete return final each label internal native ' +
        'override protected const namespace include use AS3';
    
    var secondaryKeywords = 'void super this null Infinity -Infinity NaN undefined true false is as instanceof typeof';
    
    var functionKeywords =  'function';
    
    var tertiaryKeywords = 'class package interface';
    
    var variableKeywords = 'var';
    
    var additionalKeywords =    'Null ArgumentError arguments Array Boolean Class Date DefinitionError Error EvalError ' +
            'Function int Math Namespace Number Object QName RangeError ReferenceError RegExp SecurityError ' +
            'String SyntaxError TypeError uint URIError VerifyError XML XMLList Accessibility ' +
            'AccessibilityProperties ActionScriptVersion AVM1Movie Bitmap BitmapData BitmapDataChannel ' +
            'BlendMode CapsStyle DisplayObject DisplayObjectContainer FrameLabel GradientType Graphics ' +
            'IBitmapDrawable InteractiveObject InterpolationMethod JointStyle LineScaleMode Loader LoaderInfo ' +
            'MorphShape MovieClip PixelSnapping Scene Shape SimpleButton SpreadMethod Sprite Stage StageAlign ' +
            'StageDisplayState StageQuality StageScaleMode SWFVersion EOFError IllegalOperationError ' +
            'InvalidSWFError IOError MemoryError ScriptTimeoutError StackOverflowError ActivityEvent ' +
            'AsyncErrorEvent ContextMenuEvent DataEvent ErrorEvent Event EventDispatcher EventPhase FocusEvent ' +
            'FullScreenEvent HTTPStatusEvent IEventDispatcher IMEEvent IOErrorEvent KeyboardEvent MouseEvent ' +
            'NetStatusEvent ProgressEvent SecurityErrorEvent StatusEvent SyncEvent TextEvent TimerEvent ' +
            'ExternalInterface BevelFilter BitmapFilter BitmapFilterQuality BitmapFilterType BlurFilter ' +
            'ColorMatrixFilter ConvolutionFilter DisplacementMapFilter DisplacementMapFilterMode ' +
            'DropShadowFilter GlowFilter GradientBevelFilter GradientGlowFilter ColorTransform Matrix Point ' +
            'Rectangle Transform Camera ID3Info Microphone Sound SoundChannel SoundLoaderContext SoundMixer ' +
            'SoundTransform Video FileFilter FileReference FileReferenceList IDynamicPropertyOutput ' +
            'IDynamicPropertyWriter LocalConnection NetConnection NetStream ObjectEncoding Responder ' +
            'SharedObject SharedObjectFlushStatus Socket URLLoader URLLoaderDataFormat URLRequest ' +
            'URLRequestHeader URLRequestMethod URLStream URLVariables XMLSocket PrintJob PrintJobOptions ' +
            'PrintJobOrientation ApplicationDomain Capabilities IME IMEConversionMode LoaderContext Security ' +
            'SecurityDomain SecurityPanel System AntiAliasType CSMSettings Font FontStyle FontType GridFitType ' +
            'StaticText StyleSheet TextColorType TextDisplayMode TextField TextFieldAutoSize TextFieldType TextFormat ' +
            'TextFormatAlign TextLineMetrics TextRenderer TextSnapshot ContextMenu ContextMenuBuiltInItems ' +
            'ContextMenuItem Keyboard KeyLocation Mouse ByteArray Dictionary Endian IDataInput IDataOutput ' +
            'IExternalizable Proxy Timer XMLDocument XMLNode XMLNodeType' +
            
            /* Global Methods */
            'decodeURI decodeURIComponent encodeURI encodeURIComponent escape isFinite isNaN isXMLName ' +
            'parseFloat parseInt trace unescape ' +
            
            /* Flash 10 */
            'NetStreamPlayOptions ShaderParameter NetStreamInfo DigitCase FontWeight Kerning FontDescription ' +
            'LigatureLevel RenderingMode TextElement FontPosture TextLineValidity TextLineCreationResult ' +
            'BreakOpportunity ContentElement TabStop TabAlignment JustificationStyle FontMetrics TextLineMirrorRegion ' +
            'TextLine CFFHinting ElementFormat GraphicElement DigitWidth TextJustifier TextBlock GroupElement ' +
            'TypographicCase EastAsianJustifier LineJustification SpaceJustifier FontLookup TextRotation TextBaseline ' +
            'TriangleCulling ShaderData GraphicsEndFill ColorCorrectionSupport ShaderInput GraphicsGradientFill ' +
            'GraphicsPathWinding GraphicsStroke ShaderParameterType IGraphicsStroke ShaderJob GraphicsBitmapFill ' +
            'IGraphicsData Shader GraphicsPath IGraphicsFill GraphicsShaderFill ShaderPrecision IDrawCommand ' +
            'GraphicsTrianglePath ColorCorrection GraphicsPathCommand IGraphicsPath GraphicsSolidFill ShaderFilter ' +
            'NetStreamPlayTransitions SoundCodec ContextMenuClipboardItems MouseCursor ClipboardTransferMode Clipboard ' +
            'ClipboardFormats Vector';
    
    var documentationKeywords = '@author @copy @default @deprecated @eventType @example @exampleText @exception @haxe @inheritDoc @internal @link ' +
            '@mtasc @mxmlc @param @private @return @see @serial @serialData @serialField @since @throws @usage @version';
    
	// CommonJS
	typeof(require) != 'undefined' ? SyntaxHighlighter = require('shCore').SyntaxHighlighter : null;

	function Brush()
	{
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
            //AS3
            { regex: SyntaxHighlighter.regexLib.singleLineCComments,            css: 'comments singleline' },   // one line comments
            { regex: SyntaxHighlighter.regexLib.multiLineCComments,             css: 'comments' },              // multiline comments
            { regex: SyntaxHighlighter.regexLib.doubleQuotedString,             css: 'string' },                // double quoted strings
            { regex: SyntaxHighlighter.regexLib.singleQuotedString,             css: 'string' },                // single quoted strings
            { regex: /^\\s*#.*/gm,                                              css: 'preprocessor' },          // preprocessor tags like #region and #endregion
            { regex: /\/.*\/[gism]+/g,                                          css: 'constants' },             // regex
            { regex: new RegExp(this.getKeywords(documentationKeywords), 'gm'), css: 'comments params' },       // documentation keywords
            { regex: new RegExp(this.getKeywords(primaryKeywords), 'gm'),       css: 'keyword' },               // primary keywords
            { regex: new RegExp(this.getKeywords(secondaryKeywords), 'gm'),     css: 'color2' },                // secondary keywords
            { regex: /\b([\d]+(\.[\d]+)?|0x[a-f0-9]+)\b/gi,                     css: 'value' },                 // numbers
            { regex: new RegExp(this.getKeywords(additionalKeywords), 'gm'),    css: 'color3' },                // additional keywords
            { regex: new RegExp(this.getKeywords(functionKeywords), 'gm'),      css: 'functions' },              //Functions
            { regex: new RegExp(this.getKeywords(tertiaryKeywords), 'gm'),      css: 'color1' },                 //package, class, interface
            { regex: new RegExp(this.getKeywords(variableKeywords), 'gm'),      css: 'variable' },                 //variables
		                  
            //XML
			{ regex: new XRegExp('((\\&lt;|<)\\!\\[[\\w\\s]*?\\[)', 'gm'),			css: 'color2' },	// <![ ... [
			{ regex: new XRegExp('(\\]\\](\\&gt;|>))','gm'),                        css: 'color2' }, // ]]>
			{ regex: SyntaxHighlighter.regexLib.xmlComments,												css: 'comments' },	// <!-- ... -->
			{ regex: new XRegExp('(&lt;|<)[\\s\\/\\?]*(\\w+)(?<attributes>.*?)[\\s\\/\\?]*(&gt;|>)', 'sg'), func: process },
		];
	};

	Brush.prototype	= new SyntaxHighlighter.Highlighter();
	Brush.aliases	= ['mxml', 'flex'];

	SyntaxHighlighter.brushes.Flex = Brush;

	// CommonJS
	typeof(exports) != 'undefined' ? exports.Brush = Brush : null;
})();
