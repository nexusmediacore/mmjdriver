import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
    PointElement,
    LineElement,
} from 'chart.js';
import { Bar, Line } from 'react-chartjs-2';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend, PointElement, LineElement);

const colors = [
    '#2563EB', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
    '#0EA5E9', '#6366F1', '#EC4899', '#14B8A3', '#F97316',
];

interface ChartCardProps {
    title: string;
    type: 'bar' | 'line';
    data: { labels: string[]; values: number[] };
    yAxisLabel?: string;
}

export default function ChartCard({ title, type, data, yAxisLabel }: ChartCardProps) {
    const chartData = {
        labels: data.labels,
        datasets: [
            {
                label: yAxisLabel ?? title,
                data: data.values,
                backgroundColor: type === 'bar'
                    ? colors.slice(0, data.labels.length).map((c) => c + '80')
                    : colors[0],
                borderColor: type === 'bar'
                    ? colors.slice(0, data.labels.length)
                    : colors[0],
                borderWidth: 1,
                pointRadius: type === 'line' ? 4 : 0,
                pointHoverRadius: 6,
                fill: false,
                tension: 0.3,
            },
        ],
    };

    const options = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top' as const,
                labels: { font: { size: 12 } },
            },
            title: {
                display: false,
            },
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#E2E8F0' },
                ticks: { font: { size: 11 }, color: '#64748B' },
            },
            x: {
                grid: { display: false },
                ticks: { font: { size: 11 }, color: '#64748B' },
            },
        },
    };

    return (
        <div className="rounded-xl border border-[#E2E8F0] bg-white p-6 shadow-[0_1px_3px_-1px_rgba(15,23,42,0.06)]">
            <h3 className="mb-4 text-lg font-semibold text-[#0F172A]">{title}</h3>
            <div className="h-64 w-full">
                {type === 'bar' ? <Bar options={options} data={chartData} /> : <Line options={options} data={chartData} />}
            </div>
        </div>
    );
}
