$(document).ready(function () {
  $("#registerForm").on("submit", function (e) {
    e.preventDefault(); // Prevent default form submission

    // Clear any existing messages
    $("#responseMessage").html("");

    // Send the form data using AJAX
    $.ajax({
      url: "server.php",
      type: "POST",
      data: $("#registerForm").serialize(), // Serialize form data
      success: function (response) {
        // Check if the response indicates success or failure
        if (response.success) {
          $("#responseMessage").html(
            '<p style="color:green;">' + response.message + "</p>"
          );
          $("#registerForm")[0].reset(); // Reset the form on success
        } else {
          // Display errors dynamically
          const errors = response.errors.map(
            (error) => "<li>" + error + "</li>"
          );
          $("#responseMessage").html(
            '<ul style="color:red;">' + errors.join("") + "</ul>"
          );
        }
      },
      error: function () {
        $("#responseMessage").html(
          '<p style="color:red;">An error occurred. Please try again later.</p>'
        );
      },
    });
  });
});
