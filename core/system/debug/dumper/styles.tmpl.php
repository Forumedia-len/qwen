<style>
    .df-dump {
        color: #ccc;
        font-size: 18px;
        background: #fefefe;
        padding: 1px 15px;
        border: 1px solid #ccc;
        border-radius: 5px;
        box-shadow: 0 0 5px -2px #777;
        /*white-space: pre;*/
        /*overflow: auto;*/
        margin: 0 0 1em;
    }

    .df-dump-title {
        background: #eee;
        color: #000;
        margin: -1px -15px 5px;
        padding: 3px 15px;
        font-weight: bold;
        display: block;
    }
    .df-stack-trace {
        font-size: 0.8em;
        font-family: Consolas, Lucida Console, monospace;
    }

    .df-dump-table-wrapper { padding: 0; border-radius: 0 0 5px 5px; }
    .df-dump-table-wrapper .df-stack-trace { padding: 4px 8px; }
    .df-dump-table { border: 1px solid #ccc; width: 100%; }
    .df-dump-table td { border: 1px solid #ccc; padding: 4px 8px; background: #f9f9f9;}
    .df-dump-table th { border: 1px solid #ccc; padding: 4px 8px; background: #777; color: #fff; }

    .df-dump b { color: black; }

    .df-cont { margin-left: 2em; }

    .df-cont-closed { display: none; margin-left: 2em; }

    .df-switch { border-bottom: 1px dotted #777; cursor: pointer; }

    .df-key { color: #000; font-style: normal; }

    .df-key-closed { color: #000; font-style: normal; border-bottom: 1px dotted #777; cursor: pointer; }

    .php_debug_message_box {
        font-size: 16px;
        border: 1px solid #777;
        background: #eee;
        color: #333;
        max-width: 100%;
        margin-bottom: 5px;
    }

    .php_debug_message_box_title {
        background: #BEE8A6;
        padding: 2px 4px;
        font-family: monospace;
    }

    .php_debug_message_box_footer {
        background: #FC9494;
        padding: 2px 4px;
        font-family: monospace;
        text-align: center;
    }

    .php_debug_message_box_inner {
        padding: 2px 4px;
    }

    .php_debug_message_box_inner pre {
        margin: 0 0 10px 0;
        white-space: pre;
        max-height: 500px;
        overflow-y: scroll;
    }

    .backtrace_link {
        background: #FEF9D4;
        padding: 2px 4px;
        display: block;
        font-size: 8px;
        cursor: pointer;
    }

    .backtrace_table {
        background: #FEF9D4;
        display: none;
        font-family: monospace;
    }

    .backtrace_table table {
        border: 1px solid #ddd;
        border-collapse: collapse;
        width: 100%;
    }

    .backtrace_table td {
        border: 1px solid #ddd;
    }

</style>
<script>
    function dfDumpToggle(id) {
        var E = document.getElementById(id);
        E.style.display = (E.style.display == "block") ? "none" : "block";
    }
    function backtraceToggle(id) {
        var e = document.getElementById("debug_backtrace" + id);
        e.style.display = (e.style.display == "none") ? "block" : "none";
    }
</script>
