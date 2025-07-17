// Helper: Show spinner overlay
function showSpinner(show) {
  document.getElementById('spinnerOverlay').style.display = show ? 'flex' : 'none';
}

// Fetch initial table data
function loadTables() {
  // Load DataTables here with AJAX
}

document.addEventListener('DOMContentLoaded', () => {
  loadTables();

  document.getElementById('processData').addEventListener('click', () => {
    const raw = document.getElementById('dataInput').value;
    if (!raw.trim()) return alert('Please paste some data.');
    const lines = raw.trim().split('\n');
    const delimiter = raw.includes('\t') ? '\t' : ',';
    const headers = lines[0].split(delimiter);
    const rows = lines.slice(1).map(line => {
      const cols = line.split(delimiter);
      const obj = {};
      headers.forEach((h, i) => obj[h.trim()] = cols[i]?.trim() || '');
      return obj;
    });
    const type = headers.includes('api_no') ? 'api_master' : 'module_map';
    if (!confirm(`Detected table type: ${type}. Confirm import?`)) return;
    showSpinner(true);
    fetch('ajax/import.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({type, rows})
    }).then(r => r.json()).then(res => {
      showSpinner(false);
      if (res.success) {
        alert('Data imported successfully.');
        loadTables();
      } else alert('Import failed.');
    });
  });
});
