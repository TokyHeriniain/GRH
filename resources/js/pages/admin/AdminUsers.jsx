import React, { useEffect, useState } from "react";
import api from "axios";
import {
  Card,
  Table,
  Button,
  Badge,
  Spinner,
  Form,
  Row,
  Col,
  InputGroup,
  Modal,
} from "react-bootstrap";
import { toast } from "react-toastify";
import NavigationLayout from "../../components/NavigationLayout";

const ROLE_VARIANTS = {
  Admin: "danger",
  RH: "warning",
  Manager: "primary",
  Employe: "secondary",
};

export default function AdminUsers() {
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [pagination, setPagination] = useState({});
  const [updatingUserId, setUpdatingUserId] = useState(null);
  const [search, setSearch] = useState("");
  const [roleFilter, setRoleFilter] = useState("");
  const [authUserId, setAuthUserId] = useState(null);

  // Modal création / modification utilisateur
  const [showModal, setShowModal] = useState(false);
  const [editingUser, setEditingUser] = useState(null); // null = création
  const [formUser, setFormUser] = useState({
    name: "",
    email: "",
    role: "Employe",
    password: "",
  });

  useEffect(() => {
    // Récupérer l'utilisateur connecté
    api.get("/api/user").then((res) => setAuthUserId(res.data.id));
    fetchUsers();
  }, []);

  // ---------------- FETCH UTILISATEURS ----------------
  const fetchUsers = async (pageNumber = 1) => {
    setLoading(true);
    try {
      const res = await api.get("/api/admin/users", {
        params: { page: pageNumber, search, role: roleFilter },
      });
      setUsers(res.data.data);
      setPagination(res.data);
      setPage(pageNumber);
    } catch {
      toast.error("Erreur lors du chargement des utilisateurs");
    } finally {
      setLoading(false);
    }
  };

  // ---------------- SEARCH / FILTER LIVE ----------------
  useEffect(() => {
    const delayDebounce = setTimeout(() => {
      fetchUsers(1);
    }, 400); // delay 400ms pour éviter trop de requêtes
    return () => clearTimeout(delayDebounce);
  }, [search, roleFilter]);

  // ---------------- CHANGER RÔLE ----------------
  const handleRoleChange = async (userId, role) => {
    if (userId === authUserId) {
      toast.error("Impossible de changer votre propre rôle");
      return;
    }
    setUpdatingUserId(userId);
    try {
      await api.put(`/api/admin/users/${userId}/role`, { role });
      toast.success("Rôle mis à jour");
      fetchUsers(page);
    } catch (e) {
      toast.error(e.response?.data?.error || "Erreur de mise à jour");
    } finally {
      setUpdatingUserId(null);
    }
  };

  // ---------------- SUPPRESSION ----------------
  const handleDelete = async (userId) => {
    if (userId === authUserId) {
      toast.error("Impossible de se supprimer soi-même");
      return;
    }
    if (!window.confirm("Supprimer cet utilisateur ?")) return;
    try {
      await api.delete(`/api/admin/users/${userId}`);
      toast.success("Utilisateur supprimé");
      fetchUsers(page > 1 && users.length === 1 ? page - 1 : page);
    } catch (e) {
      toast.error(e.response?.data?.error || "Suppression impossible");
    }
  };

  // ---------------- CREATE / RESET TEST USERS ----------------
  const createTestUser = async (role) => {
    try {
      await api.post("/api/admin/users/test", { role });
      toast.success(`Utilisateur test ${role} créé`);
      fetchUsers(page);
    } catch {
      toast.error("Erreur création utilisateur test");
    }
  };

  const resetTestUsers = async () => {
    if (!window.confirm("Supprimer TOUS les utilisateurs test ?")) return;
    try {
      const res = await api.delete("/api/admin/users/reset-tests");
      toast.success(res.data.message);
      fetchUsers(1);
    } catch {
      toast.error("Erreur reset utilisateurs test");
    }
  };

  // ---------------- MODAL CREATE / EDIT ----------------
  const openModal = (user = null) => {
    if (user) {
      setEditingUser(user);
      setFormUser({
        name: user.name,
        email: user.email,
        role: user.role?.name || "Employe",
        password: "",
      });
    } else {
      setEditingUser(null);
      setFormUser({ name: "", email: "", role: "Employe", password: "" });
    }
    setShowModal(true);
  };

  const handleSaveUser = async () => {
    if (!formUser.name || !formUser.email || (!editingUser && !formUser.password)) {
      toast.error("Tous les champs obligatoires");
      return;
    }
    try {
      if (editingUser) {
        await api.put(`/api/admin/users/${editingUser.id}`, formUser);
        toast.success("Utilisateur modifié");
      } else {
        await api.post("/api/admin/users", formUser);
        toast.success("Utilisateur créé");
      }
      setShowModal(false);
      fetchUsers(1);
    } catch (e) {
      toast.error(e.response?.data?.message || "Erreur utilisateur");
    }
  };
  // ---------------- RESET FILTRES ----------------
  const resetFilters = () => {
    setSearch("");
    setRoleFilter("");
    fetchUsers(1);
  };
  return (
    <NavigationLayout>
      <Card className="shadow-sm border-0 p-4">
        <h4 className="mb-3">👑 Administration des utilisateurs</h4>

        {/* FILTRES & ACTIONS */}
        <Row className="mb-3 g-2 align-items-end">
          <Col md={3}>
            <Form.Label>Recherche</Form.Label>
            <Form.Control
              placeholder="Nom ou email"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />
          </Col>

          <Col md={2}>
            <Form.Label>Filtrer par rôle</Form.Label>
            <Form.Select value={roleFilter} onChange={(e) => setRoleFilter(e.target.value)}>
              <option value="">Tous</option>
              {Object.keys(ROLE_VARIANTS).map((r) => (
                <option key={r} value={r}>{r}</option>
              ))}
            </Form.Select>
          </Col>

          <Col md={7} className="d-flex gap-2 flex-wrap">
            {["Admin", "RH", "Manager", "Employe"].map((r) => (
            
              <Button key={r} variant="outline-primary" size="sm" onClick={() => createTestUser(r)}>
                ➕ Test {r}
              </Button>
            ))}
            <Button variant="outline-success" size="sm" onClick={() => openModal()}>
              ➕ Créer utilisateur
            </Button>
            <Button variant="outline-danger" size="sm" onClick={resetTestUsers}>
              ♻ Reset tests
            </Button>
            <Button variant="outline-secondary" size="sm" onClick={resetFilters}>
              🔄 Réinitialiser filtres
            </Button>
          </Col>
        </Row>

        {/* TABLE */}
        {loading ? (
          <div className="text-center py-5">
            <Spinner animation="border" />
          </div>
        ) : (
          <Table bordered hover responsive className="align-middle">
            <thead className="table-light">
              <tr>
                <th>#</th>
                <th>Nom</th>
                <th>Email</th>
                <th>Rôle</th>
                <th className="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              {users.length === 0 ? (
                <tr><td colSpan={5} className="text-center text-muted">Aucun utilisateur</td></tr>
              ) : (
                users.map((u) => (
                  <tr key={u.id}>
                    <td>{u.id}</td>
                    <td>{u.name}</td>
                    <td>{u.email}</td>
                    <td>
                      <Badge bg={ROLE_VARIANTS[u.role?.name] || "secondary"}>
                        {u.role?.name || "Aucun"}
                      </Badge>
                    </td>
                    <td className="text-center d-flex gap-2 justify-content-center">
                      <InputGroup size="sm" style={{ width: "140px" }}>
                        <Form.Select
                          value={u.role?.name || ""}
                          disabled={updatingUserId === u.id || u.id === authUserId}
                          onChange={(e) => handleRoleChange(u.id, e.target.value)}
                        >
                          {["Admin", "RH", "Manager", "Employe"].map((r) => (
                            <option key={r} value={r}>{r}</option>
                          ))}
                        </Form.Select>
                        {updatingUserId === u.id && (
                          <InputGroup.Text className="bg-white">
                            <Spinner animation="border" size="sm" />
                          </InputGroup.Text>
                        )}
                      </InputGroup>
                      <Button variant="outline-warning" size="sm" onClick={() => openModal(u)}>
                        ✏️
                      </Button>
                      <Button variant="outline-danger" size="sm" disabled={u.id === authUserId} onClick={() => handleDelete(u.id)}>
                        🗑
                      </Button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </Table>
        )}

        {/* PAGINATION */}
        {(pagination.last_page || 1) > 1 && (
          <div className="d-flex justify-content-end gap-2">
            <Button size="sm" disabled={page === 1} onClick={() => fetchUsers(Math.max(1, page - 1))}>◀</Button>
            <span className="align-self-center">Page {page} / {pagination.last_page || 1}</span>
            <Button size="sm" disabled={page === (pagination.last_page || 1)} onClick={() => fetchUsers(Math.min(pagination.last_page || 1, page + 1))}>▶</Button>
          </div>
        )}

        {/* MODAL CREATE / EDIT */}
        <Modal show={showModal} onHide={() => setShowModal(false)} centered>
          <Modal.Header closeButton>
            <Modal.Title>{editingUser ? "Modifier utilisateur" : "Créer utilisateur"}</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <Form>
              <Form.Group className="mb-2">
                <Form.Label>Nom</Form.Label>
                <Form.Control value={formUser.name} onChange={(e) => setFormUser({...formUser, name: e.target.value})} />
              </Form.Group>
              <Form.Group className="mb-2">
                <Form.Label>Email</Form.Label>
                <Form.Control type="email" value={formUser.email} onChange={(e) => setFormUser({...formUser, email: e.target.value})} />
              </Form.Group>
              <Form.Group className="mb-2">
                <Form.Label>Mot de passe {editingUser && "(laisser vide si inchangé)"}</Form.Label>
                <Form.Control type="password" value={formUser.password} onChange={(e) => setFormUser({...formUser, password: e.target.value})} />
              </Form.Group>
              <Form.Group className="mb-2">
                <Form.Label>Rôle</Form.Label>
                <Form.Select value={formUser.role} onChange={(e) => setFormUser({...formUser, role: e.target.value})}>
                  {Object.keys(ROLE_VARIANTS).map((r) => <option key={r} value={r}>{r}</option>)}
                </Form.Select>
              </Form.Group>
            </Form>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" onClick={() => setShowModal(false)}>Annuler</Button>
            <Button variant="success" onClick={handleSaveUser}>{editingUser ? "Modifier" : "Créer"}</Button>
          </Modal.Footer>
        </Modal>
      </Card>
    </NavigationLayout>
  );
}
