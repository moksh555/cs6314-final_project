$(document).ready(function () {
  $("#loginForm").on("submit", function (e) {
    e.preventDefault(); // Prevent default form submission

    // Clear existing messages
    $("#responseMessage").html("");

    // Send login data to the server
    $.ajax({
      url: "server.php", // Server-side script
      type: "POST",
      data: {
        action: "login", // Specify the action
        phone: $("#phone").val(),
        password: $("#password").val(),
      },
      success: function (response) {
        console.log(response); // Debug response in console

        if (response.success) {
          // Show success message
          $("#responseMessage").html(
            '<p style="color:green;">' + response.message + "</p>"
          );

          // Redirect to index.html after a short delay
          setTimeout(() => {
            window.location.href = "index.html";
          }, 1000);
        } else {
          // Display errors
          const errors = response.errors
            ? response.errors.map((error) => "<li>" + error + "</li>")
            : ["An unknown error occurred."];

          $("#responseMessage").html(
            '<ul style="color:red;">' + errors.join("") + "</ul>"
          );
        }
      },
      error: function (xhr, status, error) {
        $("#responseMessage").html(
          '<p style="color:red;">An error occurred. Please try again later.</p>'
        );
      },
    });
  });
});
