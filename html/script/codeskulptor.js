var errorLine = null;
var errorLineNo = -1;


/**
 * http://stackoverflow.com/questions/5537622/dynamically-loading-css-file-using-javascript-with-callback-without-jquery
 */
function loadStyleSheet( path, fn, scope ) {
   var head = document.getElementsByTagName( 'head' )[0], // reference to document.head for appending/ removing link nodes
       link = document.createElement( 'link' );           // create the link node
   link.setAttribute( 'href', path );
   link.setAttribute( 'rel', 'stylesheet' );
   link.setAttribute( 'type', 'text/css' );
   var sheet, cssRules;
   if ( 'sheet' in link ) {
      sheet = 'sheet'; cssRules = 'cssRules';
   }
   else {
      sheet = 'styleSheet'; cssRules = 'rules';
   }
   var interval_id = setInterval( function() {                     // start checking whether the style sheet has successfully loaded
          try {
             if ( link[sheet] && link[sheet][cssRules].length ) { // SUCCESS! our style sheet has loaded
                clearInterval( interval_id );                      // clear the counters
                clearTimeout( timeout_id );
                fn.call( scope || window, true, link );           // fire the callback with success == true
             }
          } catch( e ) {} finally {}
       }, 10 ),                                                   // how often to check if the stylesheet is loaded
       timeout_id = setTimeout( function() {       // start counting down till fail
          clearInterval( interval_id );             // clear the counters
          clearTimeout( timeout_id );
          head.removeChild( link );                // since the style sheet didn't load, remove the link node from the DOM
          fn.call( scope || window, false, link ); // fire the callback with success == false
       }, 8000 );                                 // how long to wait before failing

   head.appendChild( link );  // insert the link node into the DOM and start loading the style sheet
   return link; // return the link node;
}

function log(element, text) {
    element.innerHTML = element.innerHTML + text;
}

function printOutput(text) {
    log( document.getElementById("output"), text);
}

function readBuiltInFile(file) {
    if (Sk.builtinFiles === undefined || Sk.builtinFiles["files"][file] === undefined)
        throw "File not found: '" + file + "'";
    return Sk.builtinFiles["files"][file];
}

function printError(text) {
    log( document.getElementById("output"), "<span class=\"error-line\">" + text + "</span><br>");
}

function handleException(e) {
    if (e instanceof Sk.builtin.ParseError || e instanceof Sk.builtin.SyntaxError || e instanceof Sk.builtin.IndentationError || e instanceof Sk.builtin.TokenError) {
        try {
            if (e.args.v[2] !== undefined) {
                Sk.currLineNo = e.args.v[2]
            }
            if (e.args.v[1] !== undefined) {
                Sk.currFilename = e.args.v[1].v
            }
            var t = e.args.v[3][0][1];
            var r = e.args.v[3][1][1];
            var o = e.args.v[3][2].substring(t, r);
            e.args.v[0] = e.args.v[0].sq$concat(new Sk.builtin.str(" ('" + o + "')"))
        } catch (e) {}
    }
    var i = e.tp$name + ": " + e;
    printError(i);

    var n = (Sk.currLineNo);

    if(n) {
        errorLine = editor.addLineClass(n - 1, "background", "activeline");
        errorLineNo = n;
        editor.setCursor(n - 1);
        editor.focus();
    }
    $('#run-button').click();
}

function runCode() {
    var prog = editor.getValue();
    reset();
    prog = prog.replace(/\t/g, "    ");
    try {
        Sk.pre = "output";
        Sk.currLineNo = undefined;
        Sk.currColNo = undefined;
        Sk.currFilename = undefined;
        Sk.setExecLimit(5e3);
        Sk.configure({
            output: printOutput,
            read: readBuiltInFile,
            error: handleException
        });
        printOutput("Starting script...\n");
        var start = new Date().getTime();
        Sk.importMainWithBody("<stdin>", false, prog);
        if(!Sk.simplegui && !Sk.simpleplot && !Sk.maps) {
            $('#run-button').click();
        }
    } catch(e) {
        handleException(e);
    }

    var end = new Date().getTime();
    var time = end - start;
    printOutput("Finished executing script after " + time + " milliseconds\n");
}

function reset() {
    var mypre = document.getElementById("output");
    mypre.innerHTML = '';
    if(errorLine)
    	editor.removeLineClass(errorLine, "background", "activeline");
    errorLineNo = -1;
}

function stopClick() {
    $(this).find("i").toggleClass("fa-play-circle").toggleClass("fa-stop-circle");
    $(this).unbind().click(runClick);
    if (Sk.simplegui) {
        Sk.simplegui.cleanup();
        Sk.simplegui = undefined
    }
    if (Sk.simpleplot) {
        Sk.simpleplot.cleanup();
        Sk.simpleplot = undefined
    }
    if (Sk.maps) {
        Sk.maps.cleanup();
        Sk.maps = undefined
    }
}

function runClick() {
    $(this).find("i").toggleClass("fa-play-circle").toggleClass("fa-stop-circle");
    $(this).unbind().click(stopClick);
    runCode();
}

$(function() {
    $('#run-button').click(runClick);
});

function foldFunc(cm, pos) {
    cm.foldCode(pos, {
        rangeFinder: CodeMirror.fold.indent,
        scanUp: true
    });
}

function isValidAutoCompletionCharacter(char) {
    return char.match(/^[a-z0-9_ ]+$/i);
}

function changed(cm, obj) {
	if(errorLineNo >= 0) {
        if(cm.getCursor().line + 1 !== errorLineNo) {
            return;
        }
    	cm.removeLineClass(errorLine, "background", "activeline");
	}

    if(obj.origin == "+input") {
        if(!isValidAutoCompletionCharacter(obj.text[0])) {
            return;
        }
        CodeMirror.commands.autocomplete(cm);
    }
}

function getCookie(cname) {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for(var i = 0; i <ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

theme = getCookie("scripttheme");
if(!theme){theme = "monokai";}

CodeMirror.commands.autocomplete = function(cm) {
     CodeMirror.showHint(cm, CodeMirror.pythonHint);
}

function saveCodeWithKey() {
    saveCode(1);
}

var editor = CodeMirror.fromTextArea(document.getElementById("code"), {
    mode: {
        name: "python",
        version: 2,
        singleLineStringErrors: false
    },
    gutters: ["fold-gutter", "CodeMirror-gutter"],
    lineNumbers: true,
    indentUnit: 4,
    tabMode: "indent",
    matchBrackets: true,
    theme: theme,
    extraKeys: {
        "Ctrl-R": runCode,
        "Ctrl-Space": "autocomplete",
        "Ctrl-S": saveCodeWithKey
    },
    autoCloseBrackets: {explode: true}
});

editor.on("gutterClick", foldFunc);
editor.on("change", changed);

setTimeout(function() {
    $('#loader-wrapper').fadeOut(2000, function() {
        $(this).remove();
    });
}, 2000);

var savedCode = null;

function saveCode(overwrite, suc, err) {
    var code = editor.getValue();

    if(code == savedCode) {
        toastr.warning("Du hast dieses Programm ohne Änderungen bereits gespeichert!", "Fehler!")
        return;
    }
    var codeName = $('#code-name').val().trim();
    if(codeName === "") {
        codeName = "Neues Script";
    }

    const data = {
            "save-code": null,
            "code": code,
            "overwrite": overwrite,
            "code-name": codeName
        };
    $.ajax({ 
        url: location.protocol + '//' + location.host + location.pathname,
        data: data,
        type: 'post',
        success: function(result) {
            toastr.success('Das Script mit der ID ' + result + " wurde gespeichert!", 'Geschafft!');
            history.pushState(null, null, "?id=" + result);
            savedCode = code;
            if(suc != undefined) {
                suc();
            }
        },
        error: function(error) {
            toastr.error(error, 'Fehler!');
            if(err != undefined) {
                err();
            }
        }
    });
}

$('#save-agree').click(function() {
    $('#save-modal').modal('toggle');
    saveCode( $('#overwrite-check')[0].checked ? 1 : 0 );
});

function findGetParameter(parameterName) {
    var result = null,
        tmp = [];
    var items = location.search.substr(1).split("&");
    for (var index = 0; index < items.length; index++) {
        tmp = items[index].split("=");
        if (tmp[0] === parameterName) result = decodeURIComponent(tmp[1]);
    }
    return result;
}

$('#save-button').click(function() {
    if(findGetParameter("id")) {
        $('#overwrite-check')[0].checked = true;
        $('#name-form').css("display", "none");
        $('#overwrite-check').parent("div").css("display", "block");
    } else {
        $('#overwrite-check')[0].checked = false;
        $('#overwrite-check').parent("div").css("display", "none");
        $('#name-form').css("display", "block");
    }
    $('#save-modal').modal().modal("open");
});