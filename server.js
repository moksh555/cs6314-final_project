import express from "express";
import bodyParser from "body-parser";
import cors from "cors";
import fs from "fs";
import xml2js from "xml2js";
import { parse } from "path";

const app = express();
const port = 3000;
const parser = new xml2js.Parser();
// Middleware
app.use(cors());
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));
app.use(express.static("public"));

// POST endpoint
app.post("/contact_us", (req, res) => {
  const { firstName, lastName, phone, gender, email, comment } = req.body;
  console.log(req.body);
  if (!firstName || !lastName || !phone || !gender || !email || !comment) {
    return res.status(400).send("All fields are required.");
  }

  const xmlData = `
  <contact>
    <firstName>${firstName}</firstName>
    <lastName>${lastName}</lastName>
    <phone>${phone}</phone>
    <gender>${gender}</gender>
    <email>${email}</email>
    <comment>${comment}</comment>
  </contact>\n`;

  fs.appendFile("contacts.xml", xmlData, (err) => {
    if (err) {
      console.error("Error writing to XML file:", err);
      res.status(500).send("Server Error");
    } else {
      console.log(req.body);
      res.status(200).send("Form data saved successfully");
    }
  });
});

app.post("/flight", (req, res) => {
  const {
    originCity,
    destinationCity,
    dateDepartueUse,
    dateReturnUse,
    totalPassenger,
  } = req.body;
  console.log(totalPassenger);
  // Read the XML file
  fs.readFile("flights.xml", (err, data) => {
    if (err) {
      console.error("Error reading flights.xml:", err);
      return res.status(500).send("Server error");
    }

    parser.parseString(data, (err, result) => {
      if (err) {
        console.error("Error parsing XML:", err);
        return res.status(500).send("Server error");
      }

      const flights = result.flights.flight;
      const matchingFlights = flights.filter((flight) => {
        const departureDate = new Date(flight.departureDate[0]).getTime();
        const departureDateSearch = new Date(dateDepartueUse).getTime();

        return (
          flight.origin[0] === originCity &&
          flight.destination[0] === destinationCity &&
          departureDate === departureDateSearch &&
          parseInt(flight.availableSeats[0]) >= totalPassenger
        );
      });

      // Return alternative flights if no matches
      if (matchingFlights.length === 0) {
        const alternativeFlights = flights.filter((flight) => {
          const departureDate = new Date(flight.departureDate[0]).getTime();
          const departureDateSearch = new Date(dateDepartueUse).getTime();

          return (
            flight.origin[0] === originCity &&
            flight.destination[0] === destinationCity &&
            Math.abs(departureDate - departureDateSearch) <=
              3 * 24 * 60 * 60 * 1000 &&
            parseInt(flight.availableSeats[0]) >= totalPassenger
          );
        });

        if (alternativeFlights.length > 0) {
          if (dateReturnUse) {
            const matchingReturnFlights = flights.filter((flight) => {
              const dateReturn = new Date(flight.departureDate[0]).getTime();
              const departureReturnDateSearch = new Date(
                dateReturnUse
              ).getTime();

              return (
                flight.origin[0] === destinationCity &&
                flight.destination[0] === originCity &&
                dateReturn === departureReturnDateSearch &&
                parseInt(flight.availableSeats[0]) >= totalPassenger
              );
            });

            if (matchingReturnFlights.length === 0) {
              const alternativeReturnFlights = flights.filter((flight) => {
                const departureReturnDate = new Date(
                  flight.departureDate[0]
                ).getTime();
                const departureReturnDateSearch = new Date(
                  dateReturnUse
                ).getTime();

                return (
                  flight.origin[0] === destinationCity &&
                  flight.destination[0] === originCity &&
                  Math.abs(departureReturnDate - departureReturnDateSearch) <=
                    3 * 24 * 60 * 60 * 1000 &&
                  parseInt(flight.availableSeats[0]) >= totalPassenger
                );
              });

              if (alternativeReturnFlights.length > 0) {
                return res.json({
                  message:
                    "No exact matches found. Here are alternative flights:",
                  flights: alternativeFlights,
                  retrunMessage:
                    "No exact matches found for retrun flights. Here are alternative flights:",
                  retrunFlights: alternativeReturnFlights,
                });
              }

              return res.json({
                message:
                  "No exact matches found. Here are alternative flights:",
                flights: alternativeFlights,
                retrunMessage:
                  "No return flights found within the specified date",
              });
            }
            res.json({
              message: "No exact matches found. Here are alternative flights:",
              flights: alternativeFlights,
              retrunFlights: matchingReturnFlights,
            });
          }
          return res.json({
            message: "No exact matches found. Here are alternative flights:",
            flights: alternativeFlights,
          });
        }

        return res.json({
          message: "No flights found within the specified date.",
        });
      }

      if (dateReturnUse) {
        const matchingReturnFlights = flights.filter((flight) => {
          const dateReturn = new Date(flight.departureDate[0]).getTime();
          const departureReturnDateSearch = new Date(dateReturnUse).getTime();

          return (
            flight.origin[0] === destinationCity &&
            flight.destination[0] === originCity &&
            dateReturn === departureReturnDateSearch &&
            parseInt(flight.availableSeats[0]) >= totalPassenger
          );
        });

        if (matchingReturnFlights.length === 0) {
          const alternativeReturnFlights = flights.filter((flight) => {
            const departureReturnDate = new Date(
              flight.departureDate[0]
            ).getTime();
            const departureReturnDateSearch = new Date(dateReturnUse).getTime();

            return (
              flight.origin[0] === destinationCity &&
              flight.destination[0] === originCity &&
              Math.abs(departureReturnDate - departureReturnDateSearch) <=
                3 * 24 * 60 * 60 * 1000 &&
              parseInt(flight.availableSeats[0]) >= totalPassenger
            );
          });

          if (alternativeReturnFlights.length > 0) {
            return res.json({
              flights: matchingFlights,
              retrunMessage:
                "No exact matches found for retrun flights. Here are alternative flights:",
              retrunFlights: alternativeReturnFlights,
            });
          }

          return res.json({
            flights: matchingFlights,
            retrunMessage: "No return flights found within the specified date",
          });
        }
        res.json({
          flights: matchingFlights,
          retrunFlights: matchingReturnFlights,
        });
      } else {
        res.json({ flights: matchingFlights });
      }
    });
  });
});

app.post("/add-to-cart", (req, res) => {
  const newFlight = req.body;

  fs.readFile("./cart.json", "utf8", (err, data) => {
    if (err) {
      console.error("Error reading cart.json:", err);
      return res.status(500).send("Failed to read cart data.");
    }

    let cart = JSON.parse(data).cart || [];

    cart.push(newFlight);

    const updatedCartData = { cart };
    fs.writeFile(
      "./cart.json",
      JSON.stringify(updatedCartData, null, 2),
      (err) => {
        if (err) {
          console.error("Error writing to cart.json:", err);
          return res.status(500).send("Failed to save flight to cart.");
        }
        res.status(200).send("Flight added to cart successfully.");
      }
    );
  });
});

app.post("/update-seats", async (req, res) => {
  const { flights } = req.body;

  fs.readFile("./flights.xml", "utf8", (err, data) => {
    if (err) {
      console.error("Error reading XML file:", err);
      return res.status(500).send("Error reading XML file");
    }

    const parser = new xml2js.Parser();
    const builder = new xml2js.Builder();

    parser.parseString(data, (err, result) => {
      if (err) {
        console.error("Error parsing XML:", err);
        return res.status(500).send("Error parsing XML");
      }

      const flightsData = result.flights.flight;

      for (const flight of flights) {
        const { flightId, bookedSeats } = flight;

        const xmlFlight = flightsData.find((f) => f.flightId[0] === flightId);

        if (!xmlFlight) {
          return res
            .status(404)
            .send(`Flight with ID ${flightId} not found in XML`);
        }

        const availableSeats = parseInt(xmlFlight.availableSeats[0]);
        if (availableSeats < bookedSeats) {
          return res
            .status(400)
            .send(
              `Not enough seats available for flight ID ${flightId}. Available: ${availableSeats}, Requested: ${bookedSeats}`
            );
        }

        xmlFlight.availableSeats[0] = (availableSeats - bookedSeats).toString();
      }

      const updatedXML = builder.buildObject(result);

      fs.writeFile("./flights.xml", updatedXML, (err) => {
        if (err) {
          console.error("Error writing to XML file:", err);
          return res.status(500).send("Error writing to XML file");
        }

        res.send("Seats updated successfully for all flights");
      });
    });
  });
});

const FLIGHTS_FILE = "flightBooked.json";
const HOTELS_FILE = "bookings.xml";

app.post("/book", async (req, res) => {
  try {
    console.log("Processing booking...");

    const { flights = [], hotels = [] } = req.body;
    console.log(req.body);

    if (flights.length > 0) {
      let flightBookings = [];
      if (fs.existsSync(FLIGHTS_FILE)) {
        flightBookings = JSON.parse(fs.readFileSync(FLIGHTS_FILE, "utf8"));
        try {
          flightBookings = JSON.parse(fileContent); // Ensure it's parsed as an array
          if (!Array.isArray(flightBookings)) {
            console.error(
              "Invalid flight bookings format, resetting to an empty array."
            );
            flightBookings = [];
          }
        } catch (err) {
          console.error(
            "Error parsing flight bookings JSON, resetting to an empty array.",
            err
          );
          flightBookings = [];
        }
      } else {
        console.log("Flights file does not exist. Creating a new one.");
      }
      if (!flights || !Array.isArray(flights)) {
        return res
          .status(400)
          .send("Invalid payload format. 'flights' is required.");
      }

      fs.readFile("./flights.xml", "utf8", (err, data) => {
        if (err) {
          console.error("Error reading XML file:", err);
          return res.status(500).send("Error reading XML file.");
        }

        const parser = new xml2js.Parser();
        const builder = new xml2js.Builder();

        parser.parseString(data, (err, result) => {
          if (err) {
            console.error("Error parsing XML:", err);
            return res.status(500).send("Error parsing XML.");
          }

          const flightsData = result.flights.flight;

          if (!flightsData) {
            return res
              .status(404)
              .send("No flights data found in the XML file.");
          }

          let errors = [];

          flights.forEach(({ flightId, adults, children, infants }) => {
            const flightToUpdate = flightsData.find(
              (flight) => flight.flightId[0] === flightId
            );
            adults = parseInt(adults);
            children = parseInt(children);
            infants = parseInt(infants);

            var bookedSeats = adults + children + infants;
            if (flightToUpdate) {
              const availableSeats = parseInt(flightToUpdate.availableSeats[0]);
              if (availableSeats >= bookedSeats) {
                flightToUpdate.availableSeats[0] = (
                  availableSeats - bookedSeats
                ).toString();
                console.log(
                  `Updated flight ${flightId}: New available seats: ${flightToUpdate.availableSeats[0]}`
                );
              } else {
                errors.push(
                  `Insufficient seats for flight ${flightId}. Available: ${availableSeats}, Requested: ${bookedSeats}`
                );
              }
            } else {
              errors.push(`Flight ID ${flightId} not found in XML.`);
            }
          });

          const updatedXML = builder.buildObject(result);

          fs.writeFile("./flights.xml", updatedXML, (err) => {
            if (err) {
              console.error("Error writing to XML file:", err);
              return res.status(500).send("Error writing to XML file.");
            }

            if (errors.length > 0) {
              console.warn("Some updates failed:", errors);
              return res
                .status(400)
                .json({ message: "Some updates failed", errors });
            }

            res.send("Seats updated successfully.");
          });
        });
      });
      const newFlightBookings = flights
        .filter((item) => item.type === "flight")
        .map((flight) => ({
          flightId: flight.flightId,
          origin: flight.origin,
          destination: flight.destination,
          departureDate: flight.departureDate,
          arrivalDate: flight.arrivalDate,
          price: flight.price,
          adults: flight.adults,
          children: flight.children,
          infants: flight.infants,
          passengers: flight.passengers,
        }));

      flightBookings.push(...newFlightBookings);

      fs.writeFileSync(FLIGHTS_FILE, JSON.stringify(flightBookings, null, 2));
      console.log("Flight bookings updated in JSON.");
    } else {
      console.log("No flight bookings to process.");
    }

    // Handle hotel bookings
    if (hotels.length > 0) {
      let hotelBookingsXml = "<bookings></bookings>";
      if (fs.existsSync(HOTELS_FILE)) {
        hotelBookingsXml = fs.readFileSync(HOTELS_FILE, "utf8");
      }

      const parser = new xml2js.Parser();
      const builder = new xml2js.Builder();
      fs.readFile("./hotels.json", "utf8", (err, data) => {
        if (err) {
          console.error("Error reading hotels.json:", err);
          return res.status(500).send("Error reading hotels.json.");
        }

        let hotelsData;
        try {
          hotelsData = JSON.parse(data);
        } catch (error) {
          console.error(
            "Error parsing hotels JSON, resetting to an empty array.",
            error
          );
          hotelsData = { hotels: [] };
        }

        let errors = [];
        hotels.forEach(({ hotelId, rooms }) => {
          const hotelToUpdate = hotelsData.hotels.find(
            (hotel) => hotel.hotelId === hotelId
          );

          if (hotelToUpdate) {
            const availableRooms = parseInt(hotelToUpdate.availableRooms);
            if (availableRooms >= rooms) {
              hotelToUpdate.availableRooms = availableRooms - rooms;
              console.log(
                `Updated hotel ${hotelId}: New available rooms: ${hotelToUpdate.availableRooms}`
              );
            } else {
              errors.push(
                `Insufficient rooms for hotel ${hotelId}. Available: ${availableRooms}, Requested: ${rooms}`
              );
            }
          } else {
            errors.push(`Hotel ID ${hotelId} not found in hotels.json.`);
          }
        });

        fs.writeFile(
          "./hotels.json",
          JSON.stringify(hotelsData, null, 2),
          (err) => {
            if (err) {
              console.error("Error writing to hotels.json:", err);
              return res.status(500).send("Error writing to hotels.json.");
            }

            if (errors.length > 0) {
              console.warn("Some updates failed:", errors);
              return res
                .status(400)
                .json({ message: "Some updates failed", errors });
            }

            res.send("Hotel rooms updated successfully.");
          }
        );
      });
      await parser.parseStringPromise(hotelBookingsXml).then((result) => {
        if (!result.bookings) {
          result.bookings = { booking: [] };
        }

        const newHotelBookings = hotels
          .filter((item) => item.type === "hotel")
          .map((hotel) => ({
            hotelId: hotel.hotelId,
            hotelName: hotel.hotelName,
            city: hotel.hotelCity,
            checkIn: hotel.checkIn,
            checkOut: hotel.checkOut,
            adults: hotel.adults,
            children: hotel.chidlren,
            infants: hotel.infants,
            rooms: hotel.rooms,
            price: hotel.price,
          }));

        result.bookings.booking = [
          ...(result.bookings.booking || []),
          ...newHotelBookings,
        ];

        const updatedHotelBookingsXml = builder.buildObject(result);
        fs.writeFileSync(HOTELS_FILE, updatedHotelBookingsXml);
        console.log("Hotel bookings updated in XML.");
      });
    } else {
      console.log("No hotel bookings to process.");
    }

    fs.writeFile(
      "./cart.json",
      JSON.stringify({ cart: [] }, null, 2),
      (err) => {
        if (err) {
          console.error("Error clearing the cart:", err);
          return res
            .status(500)
            .send("Booking processed, but failed to clear the cart.");
        }

        console.log("Cart cleared successfully.");
        res
          .status(200)
          .json({ message: "Booking successful! Cart has been cleared." });
      }
    );
  } catch (error) {
    console.error("Error processing booking:", error);
    res.status(500).json({ message: error.message });
  }
});

app.listen(port, () => {
  console.log(`Server running at http://localhost:${port}`);
});
