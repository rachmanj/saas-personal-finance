import { CheckOutlined, CrownOutlined } from '@ant-design/icons';
import { router } from '@inertiajs/react';
import { Button, Card, Col, Row, Space, Tag, Typography } from 'antd';

const { Title, Text, Paragraph } = Typography;

/**
 * @param {{
 *   subscription: {
 *     tier: string;
 *     subscribed: boolean;
 *     onGracePeriod: boolean;
 *     endsAt: string | null;
 *     hasStripeCustomer: boolean;
 *   };
 *   plans: Record<string, { name: string; price: number; features: string[] }>;
 * }} props
 */
export default function BillingSettings({ subscription, plans }) {
    const isPro = subscription.tier === 'pro';
    const freePlan = plans.free;
    const proPlan = plans.pro;

    const handleUpgrade = () => {
        router.post('/billing/checkout');
    };

    const handleManageBilling = () => {
        window.location.href = '/billing/portal';
    };

    return (
        <div>
            <Paragraph type="secondary">
                Choose the plan that fits your needs. Upgrade anytime to unlock Pro features.
            </Paragraph>

            {subscription.onGracePeriod && subscription.endsAt && (
                <Tag color="warning" style={{ marginBottom: 16 }}>
                    Your Pro subscription ends on {new Date(subscription.endsAt).toLocaleDateString()}
                </Tag>
            )}

            <Row gutter={[24, 24]}>
                <Col xs={24} md={12}>
                    <Card
                        title={
                            <Space>
                                <span>{freePlan.name}</span>
                                {!isPro && <Tag color="blue">Current</Tag>}
                            </Space>
                        }
                        bordered
                    >
                        <Title level={2} style={{ marginTop: 0 }}>
                            ${freePlan.price}
                            <Text type="secondary" style={{ fontSize: 16 }}>/mo</Text>
                        </Title>
                        <ul style={{ paddingLeft: 20, minHeight: 160 }}>
                            {freePlan.features.map((feature) => (
                                <li key={feature}>
                                    <Space>
                                        <CheckOutlined style={{ color: 'var(--ant-color-success)' }} />
                                        {feature}
                                    </Space>
                                </li>
                            ))}
                        </ul>
                        <Button block disabled={!isPro}>
                            {isPro ? 'Downgrade via Portal' : 'Current Plan'}
                        </Button>
                    </Card>
                </Col>

                <Col xs={24} md={12}>
                    <Card
                        title={
                            <Space>
                                <CrownOutlined style={{ color: '#faad14' }} />
                                <span>{proPlan.name}</span>
                                {isPro && <Tag color="gold">Current</Tag>}
                            </Space>
                        }
                        bordered
                        style={!isPro ? { borderColor: '#faad14' } : undefined}
                    >
                        <Title level={2} style={{ marginTop: 0 }}>
                            ${proPlan.price}
                            <Text type="secondary" style={{ fontSize: 16 }}>/mo</Text>
                        </Title>
                        <ul style={{ paddingLeft: 20, minHeight: 160 }}>
                            {proPlan.features.map((feature) => (
                                <li key={feature}>
                                    <Space>
                                        <CheckOutlined style={{ color: 'var(--ant-color-success)' }} />
                                        {feature}
                                    </Space>
                                </li>
                            ))}
                        </ul>
                        {isPro ? (
                            <Button type="primary" block onClick={handleManageBilling}>
                                Manage Subscription
                            </Button>
                        ) : (
                            <Button type="primary" block onClick={handleUpgrade}>
                                Upgrade to Pro
                            </Button>
                        )}
                    </Card>
                </Col>
            </Row>

            {isPro && subscription.hasStripeCustomer && (
                <div style={{ marginTop: 24 }}>
                    <Button type="link" onClick={handleManageBilling}>
                        Open Stripe Customer Portal
                    </Button>
                </div>
            )}
        </div>
    );
}
