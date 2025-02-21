  // Select the button, sidebar, and overlay
  const sidebarToggle = document.getElementById('sidebar-toggle');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('overlay');
  
  // Toggle the sidebar visibility and the overlay when the button is clicked
  sidebarToggle.addEventListener('click', function() {
      sidebar.classList.toggle('open');
      overlay.style.display = sidebar.classList.contains('open') ? 'block' : 'none';
  });
  
  // Close sidebar if the user clicks on the overlay
  overlay.addEventListener('click', function() {
      sidebar.classList.remove('open');
      overlay.style.display = 'none';
  });


// Hide the loading screen after 2 seconds (simulating loading)
window.addEventListener('load', function() {
        setTimeout(function() {
            document.getElementById('loading').style.display = 'none';
        }, 2000); // Change the duration (in ms) as needed
    });

// for Add Modal
var modal = document.getElementById("myModal");
var btn = document.getElementById("openModal");
var span = document.getElementsByClassName("close")[0];

// Open Add Modal
btn.onclick = function() {
modal.style.display = "block";
}

// Close Add Modal
span.onclick = function() {
modal.style.display = "none";
}

// Close Modal if clicked outside
window.onclick = function(event) {
if (event.target == modal) {
    modal.style.display = "none";
}
}

// Get the modal and buttons
var updateModal = document.getElementById("updateModal");
var updateClose = document.getElementById("updateClose");

// Get all edit buttons
var editButtons = document.querySelectorAll(".edit");

// Add event listener to each edit button to open the modal and populate the form with the current data
editButtons.forEach(function(button) {
    button.addEventListener("click", function() {
        var id = this.getAttribute("data-id");
        var name = this.getAttribute("data-name");
        var amount = this.getAttribute("data-amount");
        var date = this.getAttribute("data-date");

        // Populate the form with the current data
        document.getElementById("updateId").value = id;
        document.getElementById("updateName").value = name;
        document.getElementById("updateAmount").value = amount;
        document.getElementById("updateDate").value = date;

        // Open the modal
        updateModal.style.display = "block";
    });
});

// Close the modal when the "x" is clicked
updateClose.addEventListener("click", function() {
    updateModal.style.display = "none";
});

// Close the modal if the user clicks outside of the modal content
window.addEventListener("click", function(event) {
    if (event.target === updateModal) {
        updateModal.style.display = "none";
    }
});


// search student/////
 // search area of student
 document.getElementById('searchInput').addEventListener('keyup', function() {
    var input = this.value.toLowerCase();
    var rows = document.querySelectorAll('#dataTable tbody tr');

    rows.forEach(function(row) {
        var cells = row.getElementsByTagName('td');
        var found = false;

        for (var i = 0; i < cells.length; i++) {
            if (cells[i].textContent.toLowerCase().indexOf(input) > -1) {
                found = true;
                break;
            }
        }

        row.style.display = found ? '' : 'none';
    });
});

// dl the table ////
function downloadTableAsCSV() {
    let table = document.getElementById("myTable");
    let rows = table.rows;
    let csv = [];

    for (let i = 0; i < rows.length; i++) {
        let cells = rows[i].cells;
        let rowData = [];
        for (let j = 0; j < cells.length; j++) {
            rowData.push(cells[j].innerText);
        }
        csv.push(rowData.join(","));
    }

    let csvString = csv.join("\n");
    let blob = new Blob([csvString], { type: "text/csv" });
    let link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "transaction history.csv";
    link.click();
}