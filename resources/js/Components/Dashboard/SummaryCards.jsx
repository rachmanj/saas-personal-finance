import { Card, Col, Row, Statistic } from 'antd';
import { ArrowDownOutlined, ArrowUpOutlined, BankOutlined, DollarOutlined } from '@ant-design/icons';

export default function SummaryCards({ incomeTotal = 0, expenseTotal = 0, netWorth = 0, currency = 'IDR' }) {
    const balance = incomeTotal - expenseTotal;

    return (
        <Row gutter={[16, 16]}>
            <Col xs={24} sm={12} lg={6}>
                <Card>
                    <Statistic
                        title="Pemasukan"
                        value={incomeTotal}
                        precision={0}
                        prefix={<ArrowUpOutlined />}
                        suffix={currency}
                        valueStyle={{ color: '#52c41a' }}
                    />
                </Card>
            </Col>
            <Col xs={24} sm={12} lg={6}>
                <Card>
                    <Statistic
                        title="Pengeluaran"
                        value={expenseTotal}
                        precision={0}
                        prefix={<ArrowDownOutlined />}
                        suffix={currency}
                        valueStyle={{ color: '#ff4d4f' }}
                    />
                </Card>
            </Col>
            <Col xs={24} sm={12} lg={6}>
                <Card>
                    <Statistic
                        title="Saldo"
                        value={balance}
                        precision={0}
                        prefix={<DollarOutlined />}
                        suffix={currency}
                        valueStyle={{ color: balance >= 0 ? '#faad14' : '#ff4d4f' }}
                    />
                </Card>
            </Col>
            <Col xs={24} sm={12} lg={6}>
                <Card>
                    <Statistic
                        title="Kekayaan Bersih"
                        value={netWorth}
                        precision={0}
                        prefix={<BankOutlined />}
                        suffix={currency}
                        valueStyle={{ color: '#1677ff' }}
                    />
                </Card>
            </Col>
        </Row>
    );
}