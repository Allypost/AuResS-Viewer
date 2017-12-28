const ChartVM = data => {
    const adjustData = (data, sum) => {
        return data.map((el, i) => ({y: el, label: String.fromCharCode(65 + i), proportion: (el / sum * 100).toFixed(2)}));
    };

    const sum = data.reduce((a, b) => a + b, 0);

    const chart = new CanvasJS.Chart("plot", {
        animationEnabled: false,
        data: [
            {
                type: "column",
                indexLabel: '{y} [{proportion}"%"]',
                dataPoints: adjustData(data, sum)
            }
        ]
    });

    chart.render();

    return chart;
};
