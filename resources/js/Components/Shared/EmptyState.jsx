import { Empty } from 'antd';

/**
 * @param {{
 *   description?: import('react').ReactNode,
 *   action?: import('react').ReactNode,
 *   image?: import('react').ReactNode,
 * }} props
 */
export default function EmptyState({ description = 'No data', action, image = Empty.PRESENTED_IMAGE_SIMPLE }) {
    return (
        <Empty
            image={image}
            description={description}
            style={{ padding: '32px 0' }}
        >
            {action}
        </Empty>
    );
}
