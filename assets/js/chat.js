new Chart(document.getElementById("userChart"), {
    type: "bar",
    data: {
        labels: ["Online", "Offline"],
        datasets: [{
            label: "Số lượng người dùng",
            data: [data.users.online, data.users.offline],
            backgroundColor: [
                "rgba(255, 193, 7, 0.7)",   // vàng đẹp
                "rgba(108, 117, 125, 0.7)"  // xám trung tính
            ],
            borderColor: [
                "rgba(255, 193, 7, 1)",
                "rgba(108, 117, 125, 1)"
            ],
            borderWidth: 2,
            borderRadius: 8,      // bo góc cột đẹp phong cách CPPtj
            maxBarThickness: 50   // độ rộng cột
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: (context) => `${context.raw} người`
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1 }
            }
        }
    }
});
