import { Empty } from 'antd';

export default function UpcomingWidget({ upcoming = [] }) {
    if (upcoming.length === 0) {
        return (
            <Empty
                description="No upcoming recurring transactions"
                image={Empty.PRESENTED_IMAGE_SIMPLE}
            />
        );
    }

    return null;
}