import React from "react";
import {
  PieChart,
  Pie,
  Tooltip,
  Legend,
  ResponsiveContainer,
} from "recharts";

export default function CongesParTypeChart({ data }) {
  if (!data || data.length === 0) {
    return <p className="text-muted">Aucune donnée disponible</p>;
  }

  return (
    <div className="card shadow-sm">
      <div className="card-header fw-bold">
        Répartition des congés par type
      </div>

      <div className="card-body" style={{ height: 300 }}>
        <ResponsiveContainer width="100%" height="100%">
          <PieChart>
            <Pie
              data={data}
              dataKey="total"
              nameKey="type"
              cx="50%"
              cy="50%"
              outerRadius={100}
              label
            />
            <Tooltip />
            <Legend />
          </PieChart>
        </ResponsiveContainer>
      </div>
    </div>
  );
}
