import { Card, Empty, Typography, theme } from 'antd';
import {
  CartesianGrid, Line, LineChart, ResponsiveContainer, Tooltip, XAxis, YAxis,
} from 'recharts';
import { M3 } from '../../theme/m3Colors';

const { Text } = Typography;

function formatRupiah(value) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value);
}

export default function NetWorthTrendChart({ data = [] }) {
  const { token } = theme.useToken();

  if (!data || data.length === 0) {
    return (
      <Card title="Tren Kekayaan Bersih">
        <Empty description="Belum cukup data" />
      </Card>
    );
  }

  const isPositive = data.length > 0 ? data[data.length - 1].net_worth >= 0 : true;
  const lineColor = isPositive ? M3.success : M3.error;
  const areaGradientId = 'netWorthGradient';

  return (
    <Card title="Tren Kekayaan Bersih (12 Bulan)">
      <ResponsiveContainer width="100%" height={320}>
        <LineChart data={data}>
          <defs>
            <linearGradient id={areaGradientId} x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stopColor={lineColor} stopOpacity={0.3} />
              <stop offset="100%" stopColor={lineColor} stopOpacity={0} />
            </linearGradient>
          </defs>
          <CartesianGrid strokeDasharray="3 3" stroke={token.colorBorderSecondary} />
          <XAxis
            dataKey="month"
            tick={{ fill: token.colorTextSecondary, fontSize: 12 }}
            axisLine={{ stroke: token.colorBorder }}
            tickLine={false}
          />
          <YAxis
            tick={{ fill: token.colorTextSecondary, fontSize: 12 }}
            axisLine={false}
            tickLine={false}
            tickFormatter={(v) => `${(v / 1_000_000).toFixed(0)}M`}
          />
          <Tooltip
            formatter={(value) => formatRupiah(value)}
            labelFormatter={(label) => `Bulan: ${label}`}
            contentStyle={{
              backgroundColor: token.colorBgElevated,
              borderColor: token.colorBorder,
              borderRadius: 8,
            }}
          />
          <Line
            type="monotone"
            dataKey="net_worth"
            name="Kekayaan Bersih"
            stroke={lineColor}
            strokeWidth={2}
            dot={{ fill: lineColor, r: 4, strokeWidth: 0 }}
            activeDot={{ r: 6, fill: lineColor, stroke: token.colorBgContainer, strokeWidth: 2 }}
            fill={`url(#${areaGradientId})`}
          />
        </LineChart>
      </ResponsiveContainer>
    </Card>
  );
}
