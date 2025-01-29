<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bus Ticket</title>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Lato', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .ticket-container {
            width: 600px;
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid #ddd;
        }
        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            align-items: center;
            margin-bottom: 20px;
        }
        .ticket-header img {
            height: 40px;
        }

        .ticket-header h4 {
            font-size: 1rem;
            font-weight: 700;
            color: #555;
            margin: 0;
        }
        .route-info {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 20px;
        }
        /* .route-info {
            border-right: 1px solid #ddd;
        } */
        .route-info h5 {
            margin: 0;
            font-size: 1rem;
            font-weight: bold;
        }
        .route-info p {
            margin: 5px 0;
            font-size: 0.85rem;
            color: #555;
        }
        .duration {
            text-align: center;
            font-size: 0.9rem;
            font-weight: bold;
            color: #777;
        }
        .ticket-details {
            border-top: 1px dashed #ddd;
            border-bottom: 1px dashed #ddd;
            padding: 15px 0;
            margin: 15px 0;
        }
        .ticket-details .passenger {
            text-align: left;
        }
        .ticket-details .seat {
            text-align: right;
        }
        .details-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        .details-section .ticket-id {
            text-align: left;
        }
        .details-section .bus-number {
            text-align: right;
        }
        .details-section div {
            width: 45%;
        }
        .details-section p {
            margin: 0;
            font-size: 0.85rem;
            color: #555;
        }
        .details-section p.title {
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
            color: #888;
        }
        .total {
            font-size: 1.2rem;
            font-weight: bold;
            text-align: right;
            color: #333;
        }
        .ticket-footer {
            text-align: center;
            margin-top: 15px;
        }
        .ticket-footer img {
            width: 100px;
        }

        .company-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .company-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        .company-info h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: bold;
        }

        .company-info p {
            margin: 0;
            font-size: 0.85rem;
            color: #555;
        }

    </style>
</head>
<body>
    <div class="ticket-container">
        <div class="ticket-header">
            <div class="company-info">
                <div>
                    <img src="{{ $profile_photo }}" alt="">
                </div>
                <div>
                    <h3>{{ $company }}</h3>
                    <p>{{ $vehicle }} {{ $air_conditioned }}</p>
                </div>
            </div>
        </div>
        <div class="route-info">
            <div class="departure">
                <h5>Departure</h5>
                <p>{{ $departure }}</p>
                <p>{{ $departure_park }}</p>

                <div>
                    <p class="title">Date</p>
                    <p>{{ $departure_date }} • {{ $departure_time }}</p>
                </div>
            </div>
            <div class="duration">
                <p class="duration">{{ $duration }}</p>
            </div>
            <div class="arrival">
                <h5>Arrival</h5>
                <p>{{ $destination }}</p>
                <p>{{ $destination_park }}</p>
            </div>
        </div>
        <div class="ticket-details">
            <div class="details-section">
                <div class="passenger">
                    <p class="title">Passenger(s) Name</p>
                    <p>{{ $passenger }}</p>
                </div>
                <div class="seat">
                    <p class="title">Seat Number</p>
                    <p>{{ $seat }}</p>
                </div>
            </div>
            <div class="details-section">
                <div class="ticket-id">
                    <p class="title">Ticket ID</p>
                    <p>{{ $ticket_id }}</p>
                </div>
                <div class="bus-number">
                    <p class="title">Bus Number</p>
                    <p>{{ $bus_number }}</p>
                </div>
            </div>
        </div>
        <div class="total">&#8358;{{ number_format($price, 2) }}</div>
        <div class="ticket-footer">
            <img src="qrcode.png" alt="QR Code">
        </div>
    </div>
</body>
</html>
