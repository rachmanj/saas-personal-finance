import { Tag } from 'antd';

/**
 * @param {{ confidence?: number | null, showLabel?: boolean }} props
 */
export default function ConfidenceBadge({ confidence, showLabel = true }) {
    if (confidence == null || Number.isNaN(Number(confidence))) {
        return null;
    }

    const value = Number(confidence);
    let color = 'error';

    if (value >= 0.8) {
        color = 'success';
    } else if (value >= 0.5) {
        color = 'warning';
    }

    const percent = Math.round(value * 100);
    const label = showLabel ? `${percent}% confidence` : `${percent}%`;

    return (
        <Tag color={color} aria-label={`AI confidence ${percent} percent`}>
            {label}
        </Tag>
    );
}
