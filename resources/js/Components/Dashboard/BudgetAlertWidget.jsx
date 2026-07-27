import { Empty } from 'antd';

export default function BudgetAlertWidget({ budgets = [] }) {
    if (budgets.length === 0) {
        return (
            <Empty
                description="No active budgets"
                image={Empty.PRESENTED_IMAGE_SIMPLE}
            />
        );
    }

    return null;
}