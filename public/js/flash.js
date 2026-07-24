document.addEventListener("DOMContentLoaded", () => {

    const alerts = document.querySelectorAll(".alert");

    if (alerts.length === 0) {
        return;
    }

    setTimeout(() => {

        alerts.forEach(alert => {

            alert.classList.add("hide");

            setTimeout(() => {

                alert.remove();

            }, 500);

        });

    }, 4000);

});