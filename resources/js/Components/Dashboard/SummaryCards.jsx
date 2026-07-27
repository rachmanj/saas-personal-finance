import { Card, Col, Row, Statistic } from 'antd';
import { ArrowDownOutlined, ArrowUpOutlined, BankOutlined, DollarOutlined } from '@ant-design/icons';

export default function SummaryCards({ incomeTotal = 0, expenseTotal = 0, netWorth = 0 }) {
    const balance = incomeTotal - expenseTotal;

    return (
        <Row gutter={[16, 16]}>
            <Col xs={24} sm={12} lg={6}>
                <Card>
                    <Statistic
                        title="Income"
                        value={incomeTotal}
                        precision={2}
                        prefix={<ArrowUpOutlined />}
                        suffix="USD"
                        valueStyle={{ color: '#52c41a' }}
                    />
                </Card>
            </Col>
            <Col xs={24} sm={12} lg={6}>
                <Card>
                    <Statistic
                        title="Expense"
                        value={expenseTotal}
                        precision={2}
                        prefix={<ArrowDownOutlined />}
                        suffix="USD"
                        valueStyle={{ color: '#ff4d4f' }}
                    />
                </Card>
            </Col>
            <Col xs={24} sm={12} lg={6}>
                <Card>
                    <Statistic
                        title="Balance"
                        value={balance}
                        precision={2}
                        prefix={<DollarOutlined />}
                        suffix="USD"
                        valueStyle={{ color: balance >= 0 ? '#faad14' : '#ff4d4f' }}
                    />
                </Card>
            </Col>
            <Col xs={24} sm={12} lg={6}>
                <Card>
                    <Statistic
                        title="Net Worth"
                        value={netWorth}
                        precision={2}
                        prefix={<BankOutlined />}
                        suffix="USD"
                        valueStyle={{ color: '#1677ff' }}
                    />
                </Card>
            </Col>
        </Row>
    );
}