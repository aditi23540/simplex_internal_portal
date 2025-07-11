<!DOCTYPE html>
<html>
<head>
    <title>Smart SQL Assistant</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        input, button { font-size: 16px; padding: 10px; margin: 10px 0; width: 100%; }
        table, th, td { border: 1px solid #ccc; border-collapse: collapse; padding: 8px; }
        table { width: 100%; margin-top: 20px; }
    </style>
</head>
<body>

<h2>🧠 Ask Your Data</h2>
<input type="text" id="query" placeholder="e.g. Kitne active users hai" />
<button onclick="ask()">Ask</button>

<pre id="sql_output" style="background: #f0f0f0; padding: 10px;"></pre>
<div id="result"></div>

<script>
function ask() {
    $("#sql_output").text("Loading...");
    $("#result").html("");

    $.post("process.php", { query: $("#query").val() }, function(data) {
        let res = JSON.parse(data);
        $("#sql_output").text("Generated SQL:\n" + res.sql);

        if (res.error) {
            $("#result").html("<b style='color:red;'>Error:</b> " + res.error);
        } else if (res.result.length === 1 && Object.keys(res.result[0]).length === 1) {
            let val = Object.values(res.result[0])[0];
            $("#result").html("<h3>Result: " + val + "</h3>");
        } else {
            let html = "<table><tr>";
            for (let key in res.result[0]) html += "<th>" + key + "</th>";
            html += "</tr>";
            for (let row of res.result) {
                html += "<tr>";
                for (let key in row) html += "<td>" + row[key] + "</td>";
                html += "</tr>";
            }
            html += "</table>";
            $("#result").html(html);
        }
    });
}
</script>

</body>
</html>
