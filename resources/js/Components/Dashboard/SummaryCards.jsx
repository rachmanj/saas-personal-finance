import { Card, Col, Row, Statistic, theme } from 'antd';
import { ArrowDownOutlined, ArrowUpOutlined, BankOutlined, DollarOutlined } from '@ant-design/icons';
import { M3 } from '../../theme/m3Colors';

export default function SummaryCards({ incomeTotal = 0, expenseTotal = 0, netWorth = 0, currency = 'IDR' }) {
    const { token } = theme.useToken();
    const balance = incomeTotal - expenseTotal;

    const cardStyle = {
        borderRadius: token.borderRadiusLG,
        background: token.colorBgContainer,
    };

    return (
        <Row gutter={[16, 16]}>
            <Col xs={24} sm={12} lg={6}>
                <Card style={cardStyle}>
                    <Statistic
                        title="Pemasukan"
                        value={incomeTotal}
                        precision={0}
                        prefix={<ArrowUpOutlined />}
                        suffix={currency}
                        valueStyle={{ color: M3.success }}
                    />
                </Card>
            </Col>
            <Col xs={24} sm={12} lg={6}>
                <Card style={cardStyle}>
                    <Statistic
                        title="Pengeluaran"
                        value={expenseTotal}
                        precision={0}
                        prefix={<ArrowDownOutlined />}
                        suffix={currency}
                        valueStyle={{ color: M3.error }}
                    />
                </Card>
            </Col>
            <Col xs={24} sm={12} lg={6}>
                <Card style={cardStyle}>
                    <Statistic
                        title="Saldo"
                        value={balance}
                        precision={0}
                        prefix={<DollarOutlined />}
                        suffix={currency}
                        valueStyle={{ color: balance >= 0 ? M3.warning : M3.error }}
                    />
                </Card>
            </Col>
            <Col xs={24} sm={12} lg={6}>
                <Card style={cardStyle}>
                    <Statistic
                        title="Kekayaan Bersih"
                        value={netWorth}
                        precision={0}
                        prefix={<BankOutlined />}
                        suffix={currency}
                        valueStyle={{ color: M3.primary }}
                    />
                </Card>
            </Col>
        </Row>
    );
}
