import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Search, MessageSquare, Phone, User } from 'lucide-react';
import { useInitials } from '@/hooks/use-initials';

interface WhatsappUser {
    id: number;
    phone_number: string;
    name: string | null;
    profile_picture: string | null;
    is_active: boolean;
    last_interaction_at: string | null;
    conversations_count: number;
    roles: string[];
    last_conversation: {
        id: number;
        status: string;
        last_message_at: string | null;
    } | null;
}

interface Props {
    users: {
        data: WhatsappUser[];
        links: any[];
        current_page: number;
        last_page: number;
    };
    filters: {
        search: string | null;
    };
}

export default function WhatsappUsersIndex({ users, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(route('admin.whatsapp-users.index'), { search }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const clearSearch = () => {
        setSearch('');
        router.get(route('admin.whatsapp-users.index'), {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const getRoleBadgeColor = (role: string) => {
        const colors: Record<string, string> = {
            guest: 'bg-gray-100 text-gray-800',
            basic: 'bg-blue-100 text-blue-800',
            premium: 'bg-purple-100 text-purple-800',
            vip: 'bg-yellow-100 text-yellow-800',
        };
        return colors[role] || 'bg-gray-100 text-gray-800';
    };

    return (
        <AppLayout>
            <Head title="WhatsApp Users" />

            <div className="container mx-auto py-6 space-y-6">
                {/* Header */}
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">WhatsApp Users</h1>
                        <p className="text-muted-foreground">
                            Manage and view conversations with WhatsApp users
                        </p>
                    </div>
                </div>

                {/* Search */}
                <Card className="p-6">
                    <form onSubmit={handleSearch} className="flex gap-2">
                        <div className="relative flex-1">
                            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                type="text"
                                placeholder="Search by phone number or name..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-9"
                            />
                        </div>
                        <Button type="submit">Search</Button>
                        {filters.search && (
                            <Button type="button" variant="outline" onClick={clearSearch}>
                                Clear
                            </Button>
                        )}
                    </form>
                </Card>

                {/* Users List */}
                <div className="grid gap-4">
                    {users.data.length === 0 ? (
                        <Card className="p-12 text-center">
                            <User className="mx-auto h-12 w-12 text-muted-foreground" />
                            <h3 className="mt-4 text-lg font-semibold">No users found</h3>
                            <p className="text-sm text-muted-foreground">
                                {filters.search
                                    ? 'Try adjusting your search criteria'
                                    : 'Users will appear here when they send messages'}
                            </p>
                        </Card>
                    ) : (
                        users.data.map((user) => (
                            <Card
                                key={user.id}
                                className="p-6 hover:shadow-md transition-shadow cursor-pointer"
                                onClick={() => router.visit(route('admin.whatsapp-users.show', user.id))}
                            >
                                <div className="flex items-start gap-4">
                                    <Avatar className="h-12 w-12">
                                        <AvatarImage src={user.profile_picture || undefined} />
                                        <AvatarFallback>
                                            {useInitials(user.name || user.phone_number)}
                                        </AvatarFallback>
                                    </Avatar>

                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center gap-2 mb-1">
                                            <h3 className="font-semibold text-lg">
                                                {user.name || 'Unknown'}
                                            </h3>
                                            {!user.is_active && (
                                                <Badge variant="secondary">Inactive</Badge>
                                            )}
                                        </div>

                                        <div className="flex items-center gap-2 text-sm text-muted-foreground mb-2">
                                            <Phone className="h-4 w-4" />
                                            <span>{user.phone_number}</span>
                                        </div>

                                        <div className="flex items-center gap-2 flex-wrap">
                                            {user.roles.map((role) => (
                                                <Badge
                                                    key={role}
                                                    variant="outline"
                                                    className={getRoleBadgeColor(role)}
                                                >
                                                    {role}
                                                </Badge>
                                            ))}
                                        </div>
                                    </div>

                                    <div className="text-right space-y-1">
                                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                            <MessageSquare className="h-4 w-4" />
                                            <span>{user.conversations_count} conversations</span>
                                        </div>
                                        {user.last_interaction_at && (
                                            <p className="text-xs text-muted-foreground">
                                                Last seen {user.last_interaction_at}
                                            </p>
                                        )}
                                        {user.last_conversation && (
                                            <Badge
                                                variant={
                                                    user.last_conversation.status === 'active'
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {user.last_conversation.status}
                                            </Badge>
                                        )}
                                    </div>
                                </div>
                            </Card>
                        ))
                    )}
                </div>

                {/* Pagination */}
                {users.last_page > 1 && (
                    <div className="flex justify-center gap-2">
                        {users.links.map((link, index) => (
                            <Button
                                key={index}
                                variant={link.active ? 'default' : 'outline'}
                                disabled={!link.url}
                                onClick={() => link.url && router.visit(link.url)}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
