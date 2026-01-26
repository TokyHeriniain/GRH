import { Card, Badge } from "react-bootstrap";

export default function ComparatifCard({ label, n, n1, evolution, unite = "" }) {
  const variant =
    evolution > 0 ? "danger" : evolution < 0 ? "success" : "secondary";

  return (
    <Card className="shadow-sm border-0">
      <Card.Body>
        <h6 className="text-muted">{label}</h6>

        <div className="d-flex justify-content-between align-items-center">
          <div>
            <div><strong>N :</strong> {n} {unite}</div>
            <div><strong>N-1 :</strong> {n1} {unite}</div>
          </div>

          <Badge bg={variant} className="fs-6">
            {evolution > 0 && "+"}
            {evolution} %
          </Badge>
        </div>
      </Card.Body>
    </Card>
  );
}
