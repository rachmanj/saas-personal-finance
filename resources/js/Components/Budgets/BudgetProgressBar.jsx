import { Progress } from 'antd';

const STATUS_COLORS = {
    ok: '#52c41a',
    warning: '#faad14',
    over: '#ff4d4f',
};

/**
 * @param {{ percent: number, status: 'ok'|'warning'|'over' }} props
 */
export default function BudgetProgressBar({ percent, status }) {
    return (
        <Progress
            percent={percent}
            size="small"
            showInfo
            strokeColor={STATUS_COLORS[status] || STATUS_COLORS.ok}
        />
    );
}
