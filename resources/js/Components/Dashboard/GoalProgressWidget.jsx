import { Empty } from 'antd';

export default function GoalProgressWidget({ goals = [] }) {
    if (goals.length === 0) {
        return (
            <Empty
                description="No active saving goals"
                image={Empty.PRESENTED_IMAGE_SIMPLE}
            />
        );
    }

    return null;
}