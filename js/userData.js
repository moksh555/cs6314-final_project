$(document).ready(function () {
  // Fetch user session data from the server
  $.ajax({
    url: "server.php", // Server-side PHP script
    type: "POST",
    data: { action: "getUserData" }, // Action to fetch session data
    success: function (response) {
      if (response.success) {
        // Update the greeting with the user's name
        $("#userGreeting").text(
          `Welcome, ${response.firstName} ${response.lastName}!`
        );
      } else {
        // Default message for guests or logged-out users
        $("#userGreeting").text("Welcome, Guest!");
      }
    },
    error: function () {
      console.error("Error fetching user data from the server.");
      $("#userGreeting").text("Welcome, Guest!");
    },
  });
});
