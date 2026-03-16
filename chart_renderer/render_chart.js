const fs = require('fs');
const path = require('path');
const { ChartJSNodeCanvas } = require('chartjs-node-canvas');
const ChartDataLabels = require('chartjs-plugin-datalabels');
const {
  Chart,
  ArcElement,
  BarElement,
  LineElement,
  CategoryScale,
  LinearScale,
  PointElement,
  Legend,
  Title,
  Tooltip,
  Filler,
} = require('chart.js');

Chart.register(
  ArcElement,
  BarElement,
  LineElement,
  CategoryScale,
  LinearScale,
  PointElement,
  Legend,
  Title,
  Tooltip,
  Filler,
  ChartDataLabels
);

// Disable datalabels globally
Chart.defaults.plugins.datalabels.display = false;

async function main() {
  try {
    const inputPath = process.argv[2];
    if (!inputPath) {
      throw new Error('Missing input JSON path.');
    }

    const raw = fs.readFileSync(inputPath, 'utf8');
    const payload = JSON.parse(raw);

    const width = payload.width || 1200;
    const height = payload.height || 675;
    const output = payload.output;

    if (!output) {
      throw new Error('Missing output file path.');
    }

    const chartJSNodeCanvas = new ChartJSNodeCanvas({
      width,
      height,
      backgroundColour: 'transparent',
    });

	const payloadOptions = payload.options || {};
	const payloadPluginOptions = payloadOptions.plugins || {};
	
	const configuration = {
	  type: payload.type,
	  data: payload.data,
	  options: {
	    responsive: false,
	    animation: false,
	    ...payloadOptions,
	    plugins: {
	      title: {
	        display: !!payload.title,
	        text: payload.title || '',
	        font: { size: 22 },
	      },
	      legend: {
	        display: payload.legendDisplay !== false,
	        position: payload.legendPosition || 'bottom',
	        labels: {
	          font: { size: 14 }
	        }
	      },
	      tooltip: {
	        enabled: false
	      },
	      ...payloadPluginOptions
	    },
	    scales: payload.scales || payloadOptions.scales || {},
	    indexAxis: payload.indexAxis || payloadOptions.indexAxis || 'x'
	  }
	};

    const image = await chartJSNodeCanvas.renderToBuffer(configuration);
    fs.mkdirSync(path.dirname(output), { recursive: true });
    fs.writeFileSync(output, image);
    process.stdout.write(output + '\n');
  } catch (err) {
    console.error(err.message);
    process.exit(1);
  }
}

main();