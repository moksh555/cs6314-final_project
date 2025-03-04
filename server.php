<?php
session_start();
header('Content-Type: application/json');
ini_set('display_errors', 0); // Disable error display
ini_set('log_errors', 1);    // Enable error logging
error_log('path/to/php-error.log'); // Log errors to a file

// Database connection variables
$host = '127.0.0.1';
$user = 'root';
$password = 'moksh12345';
$database = 'travel_deals';
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'errors' => ["Database connection failed: " . $conn->connect_error]]);
    exit;
}

// Handle POST request with action parameter
// $action = $_POST['action'] ?? null;

if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? null;
} else {
    $action = $_POST['action'] ?? null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action) {
    switch ($action) {
        case 'uploadHotels':
            uploadHotels($conn);
            break;
        case 'flightCountNum':
            flightCountNum($conn);
            break;
        case 'flightWithNoInfant':
            flightWithNoInfant($conn);
            break;
        case 'retrieveMostExpensiveFlight':
            retrieveMostExpensiveFlight($conn);
            break;
        case 'flightWithInfant2child':
            handleFlightWithInfant2child($conn);
            break;
        case 'flightWithInfant':
            handleFlightWithInfant($conn);
            break;
        case 'mostExpensiveHotel':
            handleMostExpensiveHotel($conn);
            break;
        case 'adminTexasHotels':
            handleAdminTexasHotel($conn);
            break;
        case 'adminTexasFlight':
            handleAdminTexasFlight($conn);
            break;
        case 'getFlightsBySSN':
            handleFlightsBySsn($conn);
            break;
        case 'getPassengers':
            handleGetPassengers($conn);
            break;
        case 'getHotelInfo':
            handleHotelInfo($conn);
            break;
        case 'getFlightInfo':
            handleFlightInfo($conn);
            break;
        case 'getSeptemberHotelBookings':
            handleSeptHotelVooking($conn);
            break;
        case 'getSeptemberFlightBookings':
            handleSeptFlightBooking($conn);
            break;
        case 'getHotelInfo':
            handleGetHotelInfo($conn);
            break;
        case 'getFlightInfo':
            handleGetFlightInfo($conn);
            break;
        case 'fetchHotelBookingHistory':
            handleFetchHotelBookingHistory($conn);
            break;
        case 'bookHotelCart':
            handleHotelBook($conn);
            break;
        case 'fetchHotelCart':
            handlefetchHotelCart($conn);
            break;
        case 'addToCartHotels':
            handleAddToCartHotels($conn);
            break;        
        case 'searchHotels':
            handleSearchHotels($conn);
            break;
        case 'getUserData':
            handleGetUserData();
            break;
        case 'searchOWFlights':
            handleSearchFlights($conn);
            break;   
        case 'addToCart':
            handleAddToCart($conn);
            break;    
        case 'register':
            handleRegister($conn);
            break;
        case 'login':
            handleLogin($conn);
            break;
        case 'submitComment':
            handleContacts();
            break;
        case 'fetchCart':
            handleFetchCart($conn);
            break;  
        case 'fetchBookingHistory':
            handleFetchBookingHistory($conn); // Handle the fetchBookingHistory action
            break;
        case 'uploadFlights':
            handleUploadFlights($conn);
            break;
        case 'confirmBooking':
            handleConfirmBooking($conn);
            break;
        default:
            echo json_encode(['success' => false, 'errors' => ["Invalid action."]]);
            break;
    }
} else {
    echo json_encode(['success' => false, 'errors' => ["Invalid request method or missing action."]]);
}


function uploadHotels($conn){
    if (isset($_FILES['hotelFile']['tmp_name']) && $_FILES['hotelFile']['error'] == 0) {
        // Get the uploaded file
        $fileTmpPath = $_FILES['hotelFile']['tmp_name'];
        
        // Read the file content
        $jsonContent = file_get_contents($fileTmpPath);
        
        // Decode JSON content into an array
        $hotels = json_decode($jsonContent, true);
        
        // Check if the JSON is valid
        if ($hotels === null) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON file']);
            return;
        }

        // Loop through the hotels array and insert each hotel into the database
        foreach ($hotels as $hotel) {
            $hotel_id = $hotel['hotel_id'];
            $hotel_name = $hotel['hotel_name'];
            $hotel_city = $hotel['hotel_city'];
            $price = $hotel['price'];

            // Prepare and execute SQL query to insert hotel into the database
            $query = "INSERT INTO hotels (hotel_id, hotel_name, hotel_city, price) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ssss', $hotel_id, $hotel_name, $hotel_city, $price);
            $stmt->execute();
        }

        // Return success message
        echo json_encode(['success' => true, 'message' => 'Hotels uploaded successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No file uploaded or file error']);
    }
}


function flightCountNum($conn){
    $stmt = "
    SELECT 
    COUNT(DISTINCT fb.flight_id) AS num_booked_flights
FROM 
    flight_booking fb
JOIN 
    flights f ON fb.flight_id = f.flight_id
WHERE 
    fb.flight_id LIKE 'TX%' 
    AND f.arrival_date BETWEEN '2024-09-01' AND '2024-10-31';  
    ";

    $stmtResult = $conn->prepare($stmt);
    $stmtResult->execute();
    $result = $stmtResult->get_result();
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = ['type' => 'flight', 'data' => $row];
    }

    if (empty($bookings)) {
        echo json_encode(['success' => false, 'message' => 'No Flights found that land in California Between SEP 2024 AND OCT 2024']);
    } else {
        echo json_encode(['success' => true, 'bookings' => $bookings]);
    }
}

function flightWithNoInfant($conn){
    $stmt = "
    SELECT DISTINCT 
    fb.flight_id,
    f.origin,
    f.destination,
    f.departure_date,
    f.arrival_date,
    f.departure_time,
    f.arrival_time,
    f.available_seats,
    f.price AS ticket_price
FROM 
    Flight_booking fb
JOIN 
    Flights f ON fb.flight_id = f.flight_id
JOIN 
    tickets t ON fb.flight_booking_id = t.flight_booking_id
    
JOIN 
    passengers p ON t.ssn = p.ssn
WHERE 
    fb.flight_id LIKE 'TX%'  -- Flights where flight_id starts with 'TX' (indicating Texas)
GROUP BY 
    fb.flight_booking_id, fb.flight_id, f.origin, f.destination, f.departure_date, 
    f.arrival_date, f.departure_time, f.arrival_time, f.available_seats, f.price, fb.total_price
HAVING 
    COUNT(CASE WHEN p.category = 'infant' THEN 1 END) = 0;  -- No infant passengers
    ";

    $stmtResult = $conn->prepare($stmt);
    $stmtResult->execute();
    $result = $stmtResult->get_result();
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = ['type' => 'flight', 'data' => $row];
    }

    if (empty($bookings)) {
        echo json_encode(['success' => false, 'message' => 'No Flights found with no Infant']);
    } else {
        echo json_encode(['success' => true, 'bookings' => $bookings]);
    }
}


function retrieveMostExpensiveFlight($conn){
    $stmt = "
    SELECT 
    fb.flight_booking_id,
    fb.flight_id,
    f.origin,
    f.destination,
    f.departure_date,
    f.arrival_date,
    f.departure_time,
    f.arrival_time,
    f.available_seats,
    f.price AS ticket_price,
    fb.total_price AS booking_total_price,
    p.ssn,
    p.first_name,
    p.last_name,
    p.dob,
    p.category AS passenger_category
FROM 
    Flight_booking fb
JOIN 
    Flights f ON fb.flight_id = f.flight_id
JOIN 
    tickets t ON fb.flight_booking_id = t.flight_booking_id
JOIN 
    passengers p ON t.ssn = p.ssn
WHERE 
    fb.total_price = (SELECT MAX(total_price) FROM Flight_booking);
    ";
    $stmtResult = $conn->prepare($stmt);
    $stmtResult->execute();
    $result = $stmtResult->get_result();
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = ['type' => 'flight', 'data' => $row];
    }

    if (empty($bookings)) {
        echo json_encode(['success' => false, 'message' => 'No Flights found']);
    } else {
        echo json_encode(['success' => true, 'bookings' => $bookings]);
    }
}

function handleFlightWithInfant2child($conn) {
    $stmt = "
    SELECT DISTINCT 
    fb.flight_id
    FROM 
        Flight_booking fb
    JOIN 
        Tickets t ON fb.flight_booking_id = t.flight_booking_id
    JOIN 
        passengers p ON t.ssn = p.ssn
    GROUP BY 
        fb.flight_id
    HAVING 
        COUNT(CASE WHEN p.category = 'infant' THEN 1 END) > 0  
        AND COUNT(CASE WHEN p.category = 'child' THEN 1 END) = 2;
    ";

    $stmtResult = $conn->prepare($stmt);
    $stmtResult->execute();
    $result = $stmtResult->get_result();
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = ['type' => 'flight_id', 'data' => $row];
    }

    if (empty($bookings)) {
        echo json_encode(['success' => false, 'message' => 'No Flight ith infant on-Board.']);
    } else {
        echo json_encode(['success' => true, 'bookings' => $bookings]);
    }
}

function handleFlightWithInfant($conn){
    $stmt = "
    SELECT DISTINCT 
    fb.flight_id
    FROM 
        Flight_booking fb
    JOIN 
        tickets t ON fb.flight_booking_id = t.flight_booking_id
    JOIN 
        passengers p ON t.ssn = p.ssn
    WHERE 
        p.category = 'infant';
    ";

    $stmtResult = $conn->prepare($stmt);
    $stmtResult->execute();
    $result = $stmtResult->get_result();
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = ['type' => 'flight_id', 'data' => $row];
    }

    if (empty($bookings)) {
        echo json_encode(['success' => false, 'message' => 'No Flight ith infant on-Board.']);
    } else {
        echo json_encode(['success' => true, 'bookings' => $bookings]);
    }
}

function handleMostExpensiveHotel($conn){
    $stmt = '
    SELECT 
    hb.hotel_booking_id,
    hb.hotel_id,
    hb.phone,
    hb.check_in,
    hb.check_out,
    hb.rooms,
    hb.price,
    hb.total_price,
    g.ssn ,
    g.first_name ,
    g.last_name ,
    g.dob AS guest_dob,
    g.category,
    h.hotel_name,
    h.hotel_city,
    h.price AS hotel_price
FROM 
    Hotel_booking hb
JOIN 
    guest g ON hb.hotel_booking_id = g.hotel_booking_id
JOIN 
    hotels h ON hb.hotel_id = h.hotel_id
WHERE 
    hb.total_price = (SELECT MAX(total_price) FROM Hotel_booking)
ORDER BY 
    hb.total_price DESC;
    ';
    $stmtResult = $conn->prepare($stmt);
    $stmtResult->execute();
    $result = $stmtResult->get_result();
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = ['type' => 'hotel', 'data' => $row];
    }

    if (empty($bookings)) {
        echo json_encode(['success' => false, 'message' => 'No Hotel bookings found.']);
    } else {
        echo json_encode(['success' => true, 'bookings' => $bookings]);
    }
}

function handleAdminTexasHotel($conn){
    $stmt = '
    select distinct hotel_id
    from Hotel_booking
    where hotel_id LIKE "H%" AND check_in BETWEEN "2024-09-01" AND "2024-10-30";';

    $stmtResult = $conn->prepare($stmt);
    $stmtResult->execute();
    $result = $stmtResult->get_result();
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = ['type' => 'hotel_id', 'data' => $row];
    }

    if (empty($bookings)) {
        echo json_encode(['success' => false, 'message' => 'No Hotel bookings found.']);
    } else {
        echo json_encode(['success' => true, 'bookings' => $bookings]);
    }
}

function handleAdminTexasFlight($conn){
    $stmt = '
    select distinct f.flight_id from flights f 
    JOIN flight_booking fb on fb.flight_id = f.flight_id
    WHERE f.flight_id LIKE "TX%" AND f.departure_date BETWEEN "2024-09-01" AND "2024-10-30"';

    $stmtResult = $conn->prepare($stmt);
    $stmtResult->execute();
    $result = $stmtResult->get_result();
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = ['type' => 'flight_id', 'data' => $row];
    }

    if (empty($bookings)) {
        echo json_encode(['success' => false, 'message' => 'No Flight bookings found.']);
    } else {
        echo json_encode(['success' => true, 'bookings' => $bookings]);
    }
}

function handleFlightsBySsn($conn){
    $ssn = $_POST['SSN'];

    $ssnQuqery = "
    SELECT 
    fb.flight_booking_id,
    f.flight_id,
    f.origin,
    f.destination,
    f.departure_date,
    f.arrival_date,
    f.departure_time,
    f.arrival_time,
    f.price AS flight_price,
    fb.total_price AS booking_total_price,
    p.ssn,
    p.first_name,
    p.last_name,
    p.dob,
    p.category AS passenger_category,
    t.price AS ticket_price
    FROM
        passengers p
    JOIN
        tickets t ON p.ssn = t.ssn
    JOIN
        flight_booking fb ON t.flight_booking_id = fb.flight_booking_id
    JOIN
        flights f ON fb.flight_id = f.flight_id
    WHERE
        p.ssn = ?
    ";
    $ssnResult = $conn->prepare($ssnQuqery);
    $ssnResult->bind_param('s', $ssn);
    $ssnResult->execute();
    $result = $ssnResult->get_result();
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = ['type' => 'flight', 'data' => $row];
    }

    if (empty($bookings)) {
        echo json_encode(['success' => false, 'message' => 'No Flight bookings found.']);
    } else {
        echo json_encode(['success' => true, 'bookings' => $bookings]);
    }

}

function handleGetPassengers($conn){
    $flight_booking_id = $_POST['flightBookingId'];

    $passengerQuery = "
    select * 
    from tickets t
    JOIN passengers p ON p.ssn = t.ssn
    WHERE t.flight_booking_id = ?;
    ";
    $passengerResult = $conn->prepare($passengerQuery);
    $passengerResult->bind_param('s', $flight_booking_id);
    $passengerResult->execute();
    $result = $passengerResult->get_result();


    $passengers = [];
    while ($row = $result->fetch_assoc()) {
        $passengers[] = ['type' => 'passengers', 'data' => $row];
    }

    if (empty($passengers)) {
        echo json_encode(['success' => false, 'message' => 'No Flight bookings found for particular flight booking Id']);
    } else {
        echo json_encode(['success' => true, 'passengers' => $passengers]);
    }
}


function handleHotelInfo($conn){
    $phone = $_SESSION['phone'];
    $hotel_booking_id = $_POST['hotelBookingId'];

    $hotelQuery = "
    SELECT * 
    FROM Hotel_booking as hb
    JOIN guest g ON g.hotel_booking_id = hb.hotel_booking_id
    JOIN hotels h ON hb.hotel_id = h.hotel_id
    WHERE phone = ? AND
    hb.hotel_booking_id = ?
    ";
    $hotelResult = $conn->prepare($hotelQuery);
    $hotelResult->bind_param('ss', $phone, $hotel_booking_id);
    $hotelResult->execute();
    $result = $hotelResult->get_result();

    $bookings = [];
        while ($row = $result->fetch_assoc()) {
            $bookings[] = ['type' => 'hotel', 'data' => $row];
        }

        if (empty($bookings)) {
            echo json_encode(['success' => false, 'message' => 'No Hotel bookings found.']);
        } else {
            echo json_encode(['success' => true, 'bookings' => $bookings]);
        }
}


function handleFlightInfo($conn){
    $phone = $_SESSION['phone'];
    $flight_booking_id = $_POST['flightBookingId'];

    $stmt = "
    SELECT
    fb.flight_booking_id,
    f.flight_id,
    f.origin,
    f.destination,
    f.departure_date,
    f.arrival_date,
    f.departure_time,
    f.arrival_time,
    f.available_seats,
    f.price AS flight_price,
    fb.total_price AS booking_total_price,
    p.ssn,
    p.first_name,
    p.last_name,
    p.dob,
    p.category AS passenger_category,
    t.ticket_id,
    t.price AS ticket_price,
    ub.phone
FROM
    user_bookings ub
JOIN
    flight_booking fb ON ub.flight_booking_id = fb.flight_booking_id
JOIN
    flights f ON fb.flight_id = f.flight_id
JOIN
    tickets t ON fb.flight_booking_id = t.flight_booking_id
JOIN
    passengers p ON t.ssn = p.ssn
WHERE
    ub.phone = ? AND
    ub.flight_booking_id = ?;
    ";

    $stmtResult = $conn->prepare($stmt);
    $stmtResult->bind_param("ss", $phone, $flight_booking_id);
    $stmtResult->execute();
    $result = $stmtResult->get_result();
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = ['type' => 'flight', 'data' => $row];
    }

    if (empty($bookings)) {
        echo json_encode(['success' => false, 'message' => 'No Flight bookings found.']);
    } else {
        echo json_encode(['success' => true, 'bookings' => $bookings]);
    }

}

function handleSeptHotelVooking($conn){
    $phone = $_SESSION['phone'];
    $start_date = '2024-09-01';
    $end_date = '2024-09-30';

    $hotelQuery = "
    SELECT * 
    FROM Hotel_booking as hb
    JOIN guest g ON g.hotel_booking_id = hb.hotel_booking_id
    JOIN hotels h ON hb.hotel_id = h.hotel_id
    WHERE phone = ? AND
    hb.check_in BETWEEN '2024-09-01' AND '2024-09-30'
    ";
    $hotelResult = $conn->prepare($hotelQuery);
    $hotelResult->bind_param('s', $phone);
    $hotelResult->execute();
    $result = $hotelResult->get_result();

    $bookings = [];
        while ($row = $result->fetch_assoc()) {
            $bookings[] = ['type' => 'hotel', 'data' => $row];
        }

        if (empty($bookings)) {
            echo json_encode(['success' => false, 'message' => 'No Hotel bookings found for SEP 2024.']);
        } else {
            echo json_encode(['success' => true, 'bookings' => $bookings]);
        }



}



function handleSeptFlightBooking($conn){
    $phone = $_SESSION['phone'];
    $start_date = '2024-09-01';
        $end_date = '2024-09-30';

        // Fetch flight bookings for SEP 2024
        $flightQuery = "
        SELECT
        fb.flight_booking_id,
        f.flight_id,
        f.origin,
        f.destination,
        f.departure_date,
        f.arrival_date,
        f.departure_time,
        f.arrival_time,
        f.price AS flight_price,
        fb.total_price AS booking_total_price,
        p.ssn,
        p.first_name,
        p.last_name,
        p.dob,
        p.category AS passenger_category,
        t.price AS ticket_price,
        ub.phone
    FROM
        user_bookings ub
    JOIN
        flight_booking fb ON ub.flight_booking_id = fb.flight_booking_id
    JOIN
        flights f ON fb.flight_id = f.flight_id
    JOIN
        tickets t ON fb.flight_booking_id = t.flight_booking_id
    JOIN
        passengers p ON t.ssn = p.ssn
    WHERE
        ub.phone = ? AND
        f.departure_date BETWEEN '2024-09-01' AND '2024-09-30'
        ";

        $flightResult = $conn->prepare($flightQuery);
        $flightResult->bind_param('s', $phone);
        $flightResult->execute();
        $result = $flightResult->get_result();

        // Combine results
        $bookings = [];
        while ($row = $result->fetch_assoc()) {
            $bookings[] = ['type' => 'flight', 'data' => $row];
        }

        if (empty($bookings)) {
            echo json_encode(['success' => false, 'message' => 'No Flight bookings found for SEP 2024.']);
        } else {
            echo json_encode(['success' => true, 'bookings' => $bookings]);
        }
}

function handleGetHotelInfo($conn){
    $hotelBookingId = $_POST['hotelBookingId'];
        
    $sql = "SELECT * FROM hotel_booking WHERE hotel_booking_id = '$hotelBookingId'";
    $hotelResult = $conn->query($sql);
    
    if ($hotelResult->num_rows > 0) {
        echo json_encode([
            'success' => true,
            'hotel' => $hotelResult->fetch_assoc(),
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No hotel booking found.']);
    }
}


function handleGetFlightInfo($conn){
    $flightBookingId = $_POST['flightBookingId'];
        
    $sql = "SELECT * FROM flight_booking WHERE flight_booking_id = '$flightBookingId'";
    $flightResult = $conn->query($sql);
    
    if ($flightResult->num_rows > 0) {
        echo json_encode([
            'success' => true,
            'flight' => $flightResult->fetch_assoc(),
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No flight booking found.']);
    }
}


function handleFetchHotelBookingHistory($conn) {
    // Check if user is logged in by verifying phone in session
    if (!isset($_SESSION['phone'])) {
        echo json_encode(['success' => false, 'errors' => ['You must be logged in to view your booking history.']]);
        return;
    }

    $phone = $_SESSION['phone'];

    // SQL query to get all bookings and guest details for the logged-in user
    $sql = "
        SELECT 
            hb.hotel_booking_id, 
            hb.hotel_id, 
            hb.check_in, 
            hb.check_out, 
            hb.rooms, 
            hb.price, 
            hb.total_price, 
            h.hotel_name, 
            h.hotel_city,
            g.ssn, 
            g.first_name, 
            g.last_name, 
            g.dob, 
            g.category
        FROM Hotel_booking hb
        JOIN guest g ON hb.hotel_booking_id = g.hotel_booking_id
        JOIN hotels h ON hb.hotel_id = h.hotel_id
        WHERE hb.phone = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $phone);  // Bind phone from session
    $stmt->execute();
    $result = $stmt->get_result();

    // If no bookings are found, return empty array
    if ($result->num_rows > 0) {
        $bookings = [];
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
        echo json_encode(['success' => true, 'bookings' => $bookings]);
    } else {
        echo json_encode(['success' => true, 'bookings' => []]);
    }

    $stmt->close();
}


function handleHotelBook($conn){
    if (!isset($_SESSION['phone'])) {
        echo json_encode(['success' => false, 'errors' => ['You must be logged in to add items to the cart.']]);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $bookingData = $data['bookings'];
    $phone = $_SESSION['phone'];
    foreach ($bookingData as $booking) {
        // Extract details from each booking
        $bookingId = $booking['bookingId'];
        $hotelId = $booking['hotel_id'];
        $checkIn = $booking['checkIn'];
        $checkOut = $booking['checkOut'];
        $rooms = $booking['rooms'];
        $price = $booking['price'];
        $totalPrice = $booking['totalPrice'];
        // You can now process each booking (e.g., save it to the database)

        // Example of accessing passenger details:
        foreach ($booking['passenger_details'] as $passenger) {
            $type = $passenger['type'];
            $firstName = $passenger['firstName'];
            $lastName = $passenger['lastName'];
            $dob = $passenger['dob'];
            $ssn = $passenger['ssn'];

            $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM guest WHERE ssn = ?");
            $stmtCheck->bind_param("s", $ssn);
            $stmtCheck->execute();
            $result = $stmtCheck->get_result();
            $existingPassenger = $result->fetch_row()[0];

            if ($existingPassenger == 0) {
                $stmtInsertGuest = $conn->prepare("INSERT INTO guest (ssn, hotel_booking_id, first_name, last_name, dob, category)
                                                  VALUES (?, ?, ?, ?, ?, ?)");
                $stmtInsertGuest->execute([$ssn, $bookingId, $firstName, $lastName, $dob, $type]);
                $stmtInsertGuest->close();
                $stmtCheck->close();
            } else {
                $stmtCheck -> close();
                continue;
            }
        }

        $stmt = $conn->prepare("INSERT INTO Hotel_booking (hotel_booking_id, hotel_id, phone, check_in, check_out, rooms, price, total_price) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssidd",$bookingId, $hotelId, $phone, $checkIn, $checkOut, $rooms, $price, $totalPrice);
        $stmt->execute();
        $stmt->close();

        
    }
    $stmtClearCart = $conn->prepare("DELETE FROM cartHotel WHERE phone = ?");
    $stmtClearCart->bind_param("s", $phone);
    $stmtClearCart->execute();
    $stmtClearCart->close();

    echo json_encode(['success' => true, 'message' => 'Booking confirmed']);
    
}

function handlefetchHotelCart($conn) {
    if (!isset($_SESSION['phone'])) {
        echo json_encode(['success' => false, 'errors' => ['You must be logged in to add items to the cart.']]);
        return;
    }

    $phone = $_SESSION['phone'];
    $stmt = $conn->prepare("SELECT * FROM cartHotel WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }

    echo json_encode($bookings);
    $stmt->close();
}




function handleAddToCartHotels($conn) {
    // Ensure the user is logged in
    if (!isset($_SESSION['phone'])) {
        echo json_encode(['success' => false, 'errors' => ['You must be logged in to add items to the cart.']]);
        return;
    }

    // Get user phone from session
    $phone = $_SESSION['phone'];

    // Parse incoming JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    $hotelId = $data['hotelId'] ?? null;
    $hotelName = $data['hotelName'] ?? null;
    $hotelCity = $data['hotelCity'] ?? null;
    $checkIn = $data['checkIn'] ?? null;
    $checkOut = $data['checkOut'] ?? null;
    $price = $data['price'] ?? null;
    $adults = intval($data['adults'] ?? 0);
    $children = intval($data['children'] ?? 0);
    $infants = intval($data['infants'] ?? 0);
    $rooms = intval($data['rooms'] ?? 0);
    $totalPrice = floatval($data['totalPrice'] ?? 0);

    // Input validation
    $errors = [];
    if (!$hotelId || !$hotelName || !$hotelCity || !$checkIn || !$checkOut || $adults <= 0 || $rooms <= 0 || $totalPrice <= 0) {
        $errors[] = "All required fields (hotel ID, name, city, dates, adults, rooms, total price) must be filled and valid.";
    }

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        return;
    }

    // Insert data into the cartHotel table
    $stmt = $conn->prepare("
        INSERT INTO cartHotel (
            phone, hotel_id, hotel_name, city, checkIn, checkOut, adults, children, infants, rooms, price, totalPrice
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "ssssssiiiddi",
        $phone,
        $hotelId,
        $hotelName,
        $hotelCity,
        $checkIn,
        $checkOut,
        $adults,
        $children,
        $infants,
        $rooms,
        $price,
        $totalPrice
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Hotel added to cart successfully.']);
    } else {
        echo json_encode(['success' => false, 'errors' => ['Failed to add hotel to cart.']]);
    }

    $stmt->close();
}



function handleSearchHotels($conn) {
    // Check if the user is logged in
    if (!isset($_SESSION['phone'])) {
        echo json_encode(['success' => false, 'message' => 'You must be logged in to search for hotels.']);
        return;
    }

    // Retrieve POST data for hotel search
    $city = $_POST['stayCity'] ?? null;

    // Input validation
    $errors = [];
    if (!$city) {
        $errors[] = "City is required.";
    }
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        return;
    }

    // Query the hotels table for available hotels based on city
    $stmt = $conn->prepare("
        SELECT hotel_id, hotel_name, hotel_city, price
        FROM hotels 
        WHERE hotel_city = ?
        ORDER BY price ASC
    ");
    $stmt->bind_param("s", $city);

    $stmt->execute();
    $result = $stmt->get_result();

    $hotels = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $hotels[] = $row;
        }
    } else {
        echo json_encode(['success' => false, 'message' => "No hotels available in the selected city."]);
        return;
    }

    // Return available hotels as JSON response
    echo json_encode(['success' => true, 'hotels' => $hotels]);

    $stmt->close();
}





function handleFetchBookingHistory($conn) {
    // Assuming the phone number is stored in session for the logged-in user
    $phone = $_SESSION['phone'] ?? null;
    
    if (!$phone) {
        echo json_encode(['success' => false, 'errors' => ["User not logged in."]]);
        return;
    }

    $stmt = $conn->prepare("
    SELECT 
        fb.flight_booking_id, 
        fb.flight_id, 
        fb.total_price, 
        f.origin, 
        f.destination, 
        f.departure_date, 
        f.arrival_date, 
        f.departure_time, 
        f.arrival_time,
        t.ticket_id, 
        t.ssn, 
        t.price, 
        p.first_name, 
        p.last_name, 
        p.dob
    FROM flight_booking fb
    JOIN flights f ON fb.flight_id = f.flight_id
    JOIN user_bookings ub ON fb.flight_booking_id = ub.flight_booking_id
    JOIN tickets t ON fb.flight_booking_id = t.flight_booking_id
    JOIN passengers p ON t.ssn = p.ssn
    WHERE ub.phone = ?
");
$stmt->bind_param("s", $phone);  // Bind the phone number from user input
$stmt->execute();
$result = $stmt->get_result();
    
$bookingHistory = [];
while ($row = $result->fetch_assoc()) {
    // Make sure to group tickets by flight_booking_id
    $flightBookingId = $row['flight_booking_id'];

    // If it's the first time we're seeing this flight booking, initialize an entry
    if (!isset($bookingHistory[$flightBookingId])) {
        $bookingHistory[$flightBookingId] = [
            'flightBookingId' => $row['flight_booking_id'],
            'flightId' => $row['flight_id'],
            'origin' => $row['origin'],
            'destination' => $row['destination'],
            'departureDate' => $row['departure_date'],
            'arrivalDate' => $row['arrival_date'],
            'departureTime' => $row['departure_time'],
            'arrivalTime' => $row['arrival_time'],
            'totalPrice' => $row['total_price'],
            'passengerDetails' => []  // Initialize an empty array for passengers
        ];
    }

    // Add passenger details for this flight booking
    $bookingHistory[$flightBookingId]['passengerDetails'][] = [
        'ticketId' => $row['ticket_id'],
        'ssn' => $row['ssn'],
        'firstName' => $row['first_name'],
        'lastName' => $row['last_name'],
        'dob' => $row['dob'],
        'price' => $row['price']
    ];
}

// Now we can output $bookingHistory

    
    // Close the main statement
    $stmt->close();

    // Return the booking history as a JSON response
    echo json_encode([
        'success' => true,
        'bookingHistory' => $bookingHistory
    ]);
}


function handleFetchCart($conn) {
    $phone = $_SESSION['phone'] ?? null;

    if (!$phone) {
        echo json_encode(['success' => false, 'errors' => ["User not logged in."]]);
        return;
    }

    $stmt = $conn->prepare("SELECT * FROM cartFlight WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    $cartItems = [];
    while ($row = $result->fetch_assoc()) {
        $cartItems[] = $row;
    }

    echo json_encode(['success' => true, 'cartItems' => $cartItems]);
    $stmt->close();
}


function handleConfirmBooking($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    $bookingData = $data['bookingData'];

    foreach ($bookingData['flights'] as $flightBooking) {
        $bookingId = $flightBooking['booking_id'];
        error_log($bookingId);
        $flightId = $flightBooking['flightId'];
        $totalPrice = $flightBooking['totalPrice'];

        // Insert into flight-booking table
        $stmt = $conn->prepare("INSERT INTO flight_booking (flight_booking_id, flight_id, total_price) VALUES (?, ?, ?)");
        $stmt->bind_param("ssd",$bookingId, $flightId, $totalPrice);
        try {
            $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            // Log error and skip to the next flight booking
            error_log("Error inserting into flight_booking: " . $e->getMessage());
            continue;
        }
        $flightBookingId = $conn->insert_id; // Get the inserted booking ID
        $stmt->close();

        // Insert each passenger into passengers and tickets table
        foreach ($flightBooking['passengers'] as $passenger) {
            $ssnExists = 0;
            $checkStmt = $conn->prepare("SELECT COUNT(*) FROM passengers WHERE ssn = ?");
            $checkStmt->bind_param("s", $passenger['ssn']);
            $checkStmt->execute();
            $checkStmt->bind_result($ssnExists);
            $checkStmt->fetch();
            $checkStmt->close();
            $stmt = $conn->prepare("INSERT INTO tickets (flight_booking_id, ssn, price) VALUES (?, ?, ?)");
            $stmt->bind_param("ssd", $bookingId, $passenger['ssn'], $passenger['price']);
            $stmt->execute();
            $stmt->close();
            if ($ssnExists > 0) {
                // Skip this passenger if the SSN already exists
                continue;
            }
            $stmt = $conn->prepare("INSERT INTO passengers (ssn, first_name, last_name, dob, category) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "sssss",
                $passenger['ssn'],
                $passenger['firstName'],
                $passenger['lastName'],
                $passenger['dob'],
                $passenger['category']
            );
            $stmt->execute();
            $stmt->close();

            // Insert ticket data
           
        }

        // Update available seats in the flights table
        $stmt = $conn->prepare("UPDATE flights SET available_seats = available_seats - ? WHERE flight_id = ?");
        $totalPassengers = count($flightBooking['passengers']);
        $stmt->bind_param("is", $totalPassengers, $flightId);
        $stmt->execute();
        $stmt->close();
    }



    $phone = $_SESSION['phone'] ?? null;

     // Insert phone and flight booking ID into user_bookings table
     $stmt = $conn->prepare("INSERT INTO user_bookings (phone, flight_booking_id) VALUES (?, ?)");
     $stmt->bind_param("ss", $phone, $bookingId);
     $stmt->execute();
     $stmt->close();

    if ($phone) {
        $stmt = $conn->prepare("DELETE FROM cartFlight WHERE phone = ?");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $stmt->close();
    }
    echo json_encode(['success' => true, 'message' => 'Booking confirmed']);
}



function handleUploadFlights($conn) {
    if (!isset($_SESSION['phone']) || $_SESSION['phone'] !== '222-222-2222') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
        return;
    }

    if (!isset($_FILES['fileUpload'])) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
        return;
    }

    $file = $_FILES['fileUpload']['tmp_name'];
    if (!$file || !file_exists($file)) {
        echo json_encode(['success' => false, 'message' => 'File not found.']);
        return;
    }

    $xml = simplexml_load_file($file);
    if (!$xml) {
        echo json_encode(['success' => false, 'message' => 'Invalid XML file.']);
        return;
    }

    $stmt = $conn->prepare("INSERT INTO flights (flight_id, origin, destination, departure_date, arrival_date, departure_time, arrival_time, available_seats, price)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($xml->flight as $flight) {
        $stmt->bind_param(
            "sssssssss",
            $flight->flight_id,
            $flight->origin,
            $flight->destination,
            $flight->departure_date,
            $flight->arrival_date,
            $flight->departure_time,
            $flight->arrival_time,
            $flight->available_seats,
            $flight->price
        );
        $stmt->execute();
    }

    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Flights uploaded successfully.']);
}

function handleSearchFlights($conn) {
    $origin = $_POST['originCity'] ?? null;
    $destination = $_POST['destinationCity'] ?? null;
    $departureDate = $_POST['dateDepartueUse'] ?? null;
    $totalPassengers = intval($_POST['totalPassenger'] ?? 0);

    // Input validation
    $errors = [];
    if (!$origin || !$destination || !$departureDate) {
        $errors[] = "Origin, destination, and departure date are required.";
    }
    if ($totalPassengers <= 0) {
        $errors[] = "Total passengers must be greater than zero.";
    }

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => 'Moksh']);
        return;
    }

    // Convert date for SQL query
    $departureDateObj = DateTime::createFromFormat('Y-m-d', $departureDate);
    error_log($departureDate);
    if (!$departureDateObj) {
        echo json_encode(['success' => false, 'errors' => ["Invalid departure date format."]]);
        return;
    }

    $requestedDate = $departureDateObj->format('Y-m-d');
    $startDate = (clone $departureDateObj)->modify('-3 days')->format('Y-m-d');
$endDate = (clone $departureDateObj)->modify('+3 days')->format('Y-m-d');
error_log("Requested Date: $requestedDate");
error_log("Start Date: $startDate");
error_log("End Date: $endDate");
    // Query the flights table for exact date
    $stmt = $conn->prepare("
        SELECT flight_id, origin, destination, departure_date, arrival_date, departure_time, arrival_time, available_seats, price 
        FROM flights 
        WHERE origin = ? AND destination = ? AND departure_date = ? AND available_seats >= ?
        ORDER BY departure_date ASC
    ");
    $stmt->bind_param("sssi", $origin, $destination, $requestedDate, $totalPassengers);

    $stmt->execute();
    $result = $stmt->get_result();

    $flights = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $flights[] = $row;
        }
    } else {
        // If no exact matches, query for flights within 3 days before and after
        $stmt = $conn->prepare("
            SELECT flight_id, origin, destination, departure_date, arrival_date, departure_time, arrival_time, available_seats, price 
            FROM flights 
            WHERE origin = ? AND destination = ? AND departure_date BETWEEN ? AND ? AND available_seats >= ?
            ORDER BY departure_date ASC
        ");
        $stmt->bind_param("ssssi", $origin, $destination, $startDate, $endDate, $totalPassengers);

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $flights[] = $row;
            }
        }
    }

    if (!empty($flights)) {
        echo json_encode(['success' => true, 'flights' => $flights]);
    } else {
        echo json_encode(['success' => false, 'message' => "No flights available within the specified range."]);
    }

    $stmt->close();
}

// Function to handle user registration
function handleRegister($conn) {
    $phone = $_POST['phone'] ?? null;
    $password = $_POST['password'] ?? null;
    $confirmPassword = $_POST['confirmPassword'] ?? null;
    $firstName = $_POST['firstName'] ?? null;
    $lastName = $_POST['lastName'] ?? null;
    $dob = $_POST['dob'] ?? null;
    $email = $_POST['email'] ?? null;
    $gender = $_POST['gender'] ?? '';

    // Convert date format
    if (!empty($dob)) {
        $dob = DateTime::createFromFormat('m/d/Y', $dob)->format('Y-m-d');
    }

    // Validate inputs
    $errors = [];
    if (!preg_match('/^\d{3}-\d{3}-\d{4}$/', $phone)) {
        $errors[] = "Invalid phone number format. Expected format: ddd-ddd-dddd.";
    }    
    if (strlen($password) < 8 || $password !== $confirmPassword) {
        $errors[] = "Passwords must match and be at least 8 characters.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    }

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        return;
    }

    // Check for existing phone number
    $stmt = $conn->prepare("SELECT * FROM users WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'errors' => ["Phone number already registered."]]);
        return;
    }

    // Hash password and save user
  $hashedPassword = password_hash($password, PASSWORD_DEFAULT);  
    $stmt = $conn->prepare("INSERT INTO users (phone, password, firstName, lastName, dob, email, gender) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $phone, $hashedPassword, $firstName, $lastName, $dob, $email, $gender);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => "Registration successful!"]);
    } else {
        echo json_encode(['success' => false, 'errors' => ["Error saving user."]]);
    }

    $stmt->close();
}

function handleContacts() {
    // Ensure the user is logged in
    if (!isset($_SESSION['firstName'], $_SESSION['lastName'], $_SESSION['phone'])) {
        echo json_encode(['success' => false, 'message' => 'You must log in to submit a comment.']);
        return;
    }

    // Get the JSON payload
    $comment = $_POST['comment'] ?? null;


    // Validate comment
    if (strlen(trim($comment)) < 10) {
        echo json_encode(['success' => false, 'message' => 'Comment must be at least 10 characters long.']);
        return;
    }

    // Get user details from the session
    $firstName = $_SESSION['firstName'];
    $lastName = $_SESSION['lastName'];
    $phone = $_SESSION['phone'];
    $gender = $_SESSION['gender'] ?? 'Not Specified'; // Optional gender
    $email = $_SESSION['email'] ?? 'Not Specified';  // Optional email

    // Assign a unique contact ID
    $contactId = uniqid('contact-', true);

    // Prepare XML data
    $file = 'contacts.xml';
    if (!file_exists($file)) {
        // Create a new XML file with a root element if it doesn't exist
        $initialData = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<contacts>\n</contacts>";
        file_put_contents($file, $initialData);
    }

    // Load the existing XML file
    $xml = simplexml_load_file($file);
    $newContact = $xml->addChild('contact');
    $newContact->addChild('contactId', htmlspecialchars($contactId));
    $newContact->addChild('firstName', htmlspecialchars($firstName));
    $newContact->addChild('lastName', htmlspecialchars($lastName));
    $newContact->addChild('phone', htmlspecialchars($phone));
    $newContact->addChild('gender', htmlspecialchars($gender));
    $newContact->addChild('email', htmlspecialchars($email));
    $newContact->addChild('comment', htmlspecialchars($comment));

    // Save the updated XML file
    if ($xml->asXML($file)) {
        echo json_encode(['success' => true, 'message' => 'Your comment has been submitted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save your comment.']);
    }
}

function handleAddToCart($conn) {
    // Ensure the user is logged in
    if (!isset($_SESSION['phone'])) {
        echo json_encode(['success' => false, 'errors' => ['You must be logged in to add items to the cart.']]);
        return;
    }

    $phone = $_SESSION['phone']; // Use the phone number to identify the user
    $flightId = $_POST['flightId'] ?? null;
    $passengers = $_POST['totalPassengers'] ?? null;
    $adults = $_POST['adults'] ?? null;
    $children = $_POST['children'] ?? null;
    $infants = $_POST['infants'] ?? null;

    if (!$flightId || !$passengers) {
        echo json_encode(['success' => false, 'errors' => ['Flight ID and passengers are required.']]);
        return;
    }

    // Fetch flight details from the database
    $stmt = $conn->prepare("SELECT * FROM flights WHERE flight_id = ?");
    $stmt->bind_param("s", $flightId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'errors' => ['Flight not found.']]);
        return;
    }

    $flight = $result->fetch_assoc();

    // Check if there are enough available seats
    if ($flight['available_seats'] < $passengers) {
        echo json_encode(['success' => false, 'errors' => ['Not enough seats available for this flight.']]);
        return;
    }

    // Insert flight details into the cart table
    $stmt = $conn->prepare("
        INSERT INTO cartFlight (phone, flight_id, origin, destination, departure_date, arrival_date, departure_time, arrival_time, price, adults, children, infants)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "ssssssssdiii",
        $phone,
        $flight['flight_id'],
        $flight['origin'],
        $flight['destination'],
        $flight['departure_date'],
        $flight['arrival_date'],
        $flight['departure_time'],
        $flight['arrival_time'],
        $flight['price'],
        $adults,
        $children,
        $infants
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Flight added to cart successfully.']);
    } else {
        echo json_encode(['success' => false, 'errors' => ['Failed to add flight to cart.']]);
    }

    $stmt->close();
}





function handleLogin($conn) {
    session_start(); // Start the session to store user data

    $phone = $_POST['phone'] ?? null;
    $password = $_POST['password'] ?? null;

    // Validate inputs
    if (empty($phone) || empty($password)) {
        echo json_encode(['success' => false, 'errors' => ["Phone number and password are required."]]);
        return;
    }

    // Check if user exists
    $stmt = $conn->prepare("SELECT password, firstName, lastName,email,gender FROM users WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'errors' => ["Invalid phone number or password."]]);
        return;
    }

    $user = $result->fetch_assoc();

    // Verify the password
    if (password_verify($password, $user['password'])) {
        // Store user details in session
        $isAdmin = ($phone === '222-222-2222'); 
        $_SESSION['firstName'] = $user['firstName'];
        $_SESSION['lastName'] = $user['lastName'];
        $_SESSION['phone'] = $phone;
        $_SESSION['email'] = $user['email'];
        $_SESSION['gender'] = $user['gender'];
        $_SESSION['isAdmin'] = $isAdmin;;

        echo json_encode([
            'success' => true,
            'message' => $isAdmin ? "Welcome, Admin!" : "Login successful!",
            'firstName' => $user['firstName'],
            'lastName' => $user['lastName'],
            'isAdmin' => $isAdmin,
        ]);
    } else {
        echo json_encode(['success' => false, 'errors' => ["Invalid phone number or password."]]);
    }

    $stmt->close();
}

function handleGetUserData() {
    if (!isset($_SESSION['firstName']) || !isset($_SESSION['lastName'])) {
        echo json_encode(['success' => false, 'errors' => ["User not logged in."]]);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'firstName' => $_SESSION['firstName'],
        'lastName' => $_SESSION['lastName'],
        'phone' => $_SESSION['phone'],
        'isAdmin' => $_SESSION['isAdmin']
    ]);
}

?>