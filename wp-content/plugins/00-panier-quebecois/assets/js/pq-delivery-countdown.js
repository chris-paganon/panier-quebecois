jQuery(document).ready(function ($) {

    var next_delivery_deadline = next_delivery_deadline_object.next_delivery_deadline
    var countDownDate = new Date(next_delivery_deadline).getTime();

    // Update the count down every 1 second
    var x = setInterval(function() {

        // Get today's date and time
        var now = new Date().getTime();

        // Find the distance between now and the count down date
        var distance = countDownDate - now;

        // Time calculations for days, hours, minutes and seconds
        var days = Math.floor(distance / (1000 * 60 * 60 * 24));
        var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((distance % (1000 * 60)) / 1000);

        // Display the result in the element with id="demo"
        $(".pq_days_digit").html(days);
        $(".pq_hours_digit").html(hours);
        $(".pq_minutes_digit").html(minutes);
        $(".pq_seconds_digit").html(seconds);
    }, 1000);
});