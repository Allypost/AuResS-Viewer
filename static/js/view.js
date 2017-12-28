const ChartVM = config => {
    const ctx = document.getElementById('plot').getContext('2d');

    const sum = arr => arr.reduce((a, b) => a + b, 0);
    const isEqual = (a, b) => {
        // Get the value type
        const type = Object.prototype.toString.call(a);

        // If the two objects are not the same type, return false
        if (type !== Object.prototype.toString.call(b))
            return false;

        // If items are not an object or array, return false
        if (['[object Array]', '[object Object]'].indexOf(type) < 0)
            return false;

        // Compare the length of the length of the two items
        const valueLen = type === '[object Array]' ? a.length : Object.keys(a).length;
        const otherLen = type === '[object Array]' ? b.length : Object.keys(b).length;

        if (valueLen !== otherLen)
            return false;

        // Compare two items
        const compare = function (item1, item2) {
            // Get the object type
            const itemType = Object.prototype.toString.call(item1);

            // If an object or array, compare recursively
            if (['[object Array]', '[object Object]'].indexOf(itemType) >= 0) {
                if (!isEqual(item1, item2))
                    return false;
            } else { // Otherwise, do a simple comparison
                // If the two items are not the same type, return false
                if (itemType !== Object.prototype.toString.call(item2))
                    return false;

                // Else if it's a function, convert to a string and compare
                // Otherwise, just compare
                if (itemType === '[object Function]') {
                    if (item1.toString() !== item2.toString())
                        return false;
                } else {
                    if (item1 !== item2)
                        return false;
                }

            }
        };

        // Compare properties
        if (type === '[object Array]') {
            for (let i = 0; i < valueLen; i++)
                if (compare(a[i], b[i]) === false)
                    return false;
        } else {
            for (const key in a) {
                if (
                    a.hasOwnProperty(key)
                    && compare(a[key], b[key]) === false
                )
                    return false;
            }
        }

        // If nothing failed, return true
        return true;

    };
    const genLabels = arr => arr.map((el, i) => String.fromCharCode(65 + i));

    const data = config['data'];
    const type = config['type'] || 'bar';
    const dataURL = config['dataURL'];
    const labels = genLabels(data);

    let dataSum = sum(data);

    const barColours = ['#a10505', '#d87103', '#e6c003', '#78b716', '#798080', '#3093c7', '#575d07'];

    const addText = function (animation) {
        const width = this.data.datasets[0]['_meta'][0]['data'][0]['_model']['width'];
        const fontSize = Math.ceil(width / 8);

        const ctx = this.chart.ctx;

        ctx.font = Chart.helpers.fontString(
            fontSize,
            Chart.defaults.global.defaultFontStyle,
            'monospace'
        );
        ctx.textAlign = 'center';
        ctx.textBaseline = 'bottom';

        this.data.datasets.forEach(dataset => {
            for (let i = 0; i < dataset.data.length; i++) {
                const meta = Object.keys(dataset._meta)[0];
                const dataKey = dataset._meta[meta].data[i];

                const model = dataKey._model;
                const scale_max = dataKey._yScale.maxHeight;

                ctx.fillStyle = '#fff';

                // Make sure data value does not get overflown and hidden
                // when the bar's value is too close to max value of scale
                // Note: The y value is reverse, it counts from top down
                const overflows = (scale_max - model.y) / scale_max >= (scale_max - fontSize * 1.4) / scale_max;
                const y_pos = model.y + (fontSize + 5) * overflows;

                const value = dataset.data[i];
                const percent = (value / dataSum * 100).toFixed(2);

                const text = `${value} (${percent}%)`;

                ctx.fillText(text, model.x, y_pos);
            }
        });
    };

    const animation = type === 'bar' ? {
        duration: 800,
        onComplete: addText
    } : {};

    const scales = {
        xAxes: [{
            gridLines: {
                display: false
            }
        }],
        yAxes: [{
            stacked: true,
            gridLines: {
                display: true,
                color: 'rgba(255,255,255,0.2)'
            }
        }]
    };

    const options = {
        events: false,
        tooltips: {
            enabled: false
        },
        legend: {
            display: false
        },
        maintainAspectRatio: false,
        scales,
        animation
    };

    const chart = new Chart(ctx, {
        type,
        options,
        data: {
            labels,
            datasets: [
                {
                    label: '# of votes',
                    backgroundColor: barColours,
                    data
                }
            ]
        }
    });

    const updateData = data => {
        const chartData = chart['data'];
        const oldData = chartData['datasets'][0]['data'];

        if (isEqual(oldData, data))
            return;

        dataSum = sum(data);

        chartData['labels'] = genLabels(data);
        chartData['datasets'][0]['data'] = data;

        chart.update();
    };
    const fetchData = url =>
        $.get(url)
         .done(resp => {
             const data = resp['data']['data'];

             updateData(data);
         })
         .fail(resp => {
             console.warn('|> Fetch error', resp);
         })
         .always(() => {
             window.setTimeout(() => {
                 fetchData(dataURL);
             }, 1000);
         });

    window.setTimeout(() => {
        fetchData(dataURL);
    }, 1000);

    return chart;
};
