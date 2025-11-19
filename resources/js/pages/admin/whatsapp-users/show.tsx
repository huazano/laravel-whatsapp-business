import { useState, useEffect, useRef, useCallback } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Skeleton } from '@/components/ui/skeleton';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
    ArrowLeft,
    Send,
    Phone,
    User,
    Calendar,
    Shield,
    Loader2,
    Check,
    CheckCheck,
} from 'lucide-react';
import { useInitials } from '@/hooks/use-initials';
import axios from 'axios';
import { cn } from '@/lib/utils';

interface Message {
    id: number;
    whatsapp_message_id: string | null;
    direction: 'inbound' | 'outbound';
    type: string;
    content: string | null;
    media_url: string | null;
    status: 'sent' | 'delivered' | 'read' | 'failed' | 'pending';
    sent_at: string | null;
    delivered_at: string | null;
    read_at: string | null;
    created_at: string;
}

interface WhatsappUser {
    id: number;
    phone_number: string;
    name: string | null;
    profile_picture: string | null;
    is_active: boolean;
    last_interaction_at: string | null;
    created_at: string;
    roles: string[];
    permissions: string[];
}

interface Conversation {
    id: number;
    status: string;
    last_message_at: string | null;
}

interface Props {
    user: WhatsappUser;
    conversation: Conversation | null;
}

export default function WhatsappUserShow({ user, conversation }: Props) {
    const [messages, setMessages] = useState<Message[]>([]);
    const [newMessage, setNewMessage] = useState('');
    const [loading, setLoading] = useState(false);
    const [sending, setSending] = useState(false);
    const [hasMore, setHasMore] = useState(true);
    const [oldestId, setOldestId] = useState<number | null>(null);
    const [loadingMore, setLoadingMore] = useState(false);
    const scrollAreaRef = useRef<HTMLDivElement>(null);
    const messagesEndRef = useRef<HTMLDivElement>(null);
    const [isInitialLoad, setIsInitialLoad] = useState(true);

    // Load initial messages
    useEffect(() => {
        if (conversation) {
            loadMessages();
        }
    }, [conversation?.id]);

    const loadMessages = async (beforeId?: number) => {
        if (!conversation) return;

        try {
            if (beforeId) {
                setLoadingMore(true);
            } else {
                setLoading(true);
            }

            const response = await axios.get(
                route('admin.conversations.messages', conversation.id),
                {
                    params: beforeId ? { before_id: beforeId } : {},
                }
            );

            const newMessages = response.data.messages;

            if (beforeId) {
                setMessages((prev) => [...newMessages, ...prev]);
            } else {
                setMessages(newMessages);
                setIsInitialLoad(false);
            }

            setHasMore(response.data.has_more);
            if (response.data.oldest_id) {
                setOldestId(response.data.oldest_id);
            }
        } catch (error) {
            console.error('Error loading messages:', error);
        } finally {
            setLoading(false);
            setLoadingMore(false);
        }
    };

    const handleSendMessage = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!newMessage.trim() || !conversation || sending) return;

        setSending(true);
        const messageToSend = newMessage;
        setNewMessage('');

        try {
            const response = await axios.post(
                route('admin.conversations.send-message', conversation.id),
                { message: messageToSend }
            );

            setMessages((prev) => [...prev, response.data.message]);
            scrollToBottom();
        } catch (error) {
            console.error('Error sending message:', error);
            setNewMessage(messageToSend);
        } finally {
            setSending(false);
        }
    };

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    useEffect(() => {
        if (!isInitialLoad && messages.length > 0) {
            scrollToBottom();
        }
    }, [messages.length]);

    const handleScroll = useCallback(
        (e: React.UIEvent<HTMLDivElement>) => {
            const target = e.target as HTMLDivElement;
            if (target.scrollTop === 0 && hasMore && !loadingMore && oldestId) {
                const previousHeight = target.scrollHeight;
                loadMessages(oldestId).then(() => {
                    requestAnimationFrame(() => {
                        const newHeight = target.scrollHeight;
                        target.scrollTop = newHeight - previousHeight;
                    });
                });
            }
        },
        [hasMore, loadingMore, oldestId]
    );

    const formatMessageTime = (date: string) => {
        return new Date(date).toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const getStatusIcon = (message: Message) => {
        if (message.direction === 'inbound') return null;

        if (message.status === 'read') {
            return <CheckCheck className="h-3 w-3 text-blue-500" />;
        } else if (message.status === 'delivered') {
            return <CheckCheck className="h-3 w-3 text-gray-400" />;
        } else if (message.status === 'sent') {
            return <Check className="h-3 w-3 text-gray-400" />;
        } else if (message.status === 'pending') {
            return <Loader2 className="h-3 w-3 text-gray-400 animate-spin" />;
        }
        return null;
    };

    return (
        <AppLayout>
            <Head title={`Chat with ${user.name || user.phone_number}`} />

            <div className="h-[calc(100vh-4rem)] flex flex-col">
                {/* Header */}
                <div className="border-b bg-background p-4">
                    <div className="container mx-auto flex items-center gap-4">
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => router.visit(route('admin.whatsapp-users.index'))}
                        >
                            <ArrowLeft className="h-5 w-5" />
                        </Button>

                        <Avatar className="h-10 w-10">
                            <AvatarImage src={user.profile_picture || undefined} />
                            <AvatarFallback>
                                {useInitials(user.name || user.phone_number)}
                            </AvatarFallback>
                        </Avatar>

                        <div className="flex-1">
                            <h2 className="font-semibold">{user.name || 'Unknown'}</h2>
                            <p className="text-sm text-muted-foreground flex items-center gap-1">
                                <Phone className="h-3 w-3" />
                                {user.phone_number}
                            </p>
                        </div>

                        <div className="flex gap-2">
                            {user.roles.map((role) => (
                                <Badge key={role} variant="outline">
                                    {role}
                                </Badge>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Messages Area */}
                <div className="flex-1 overflow-hidden bg-gray-50">
                    <div
                        ref={scrollAreaRef}
                        onScroll={handleScroll}
                        className="h-full overflow-y-auto"
                    >
                        <div className="container mx-auto py-4 space-y-4">
                            {loadingMore && (
                                <div className="flex justify-center">
                                    <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
                                </div>
                            )}

                            {loading ? (
                                <div className="space-y-4">
                                    {[1, 2, 3, 4, 5].map((i) => (
                                        <div
                                            key={i}
                                            className={cn(
                                                'flex',
                                                i % 2 === 0 ? 'justify-end' : 'justify-start'
                                            )}
                                        >
                                            <Skeleton className="h-16 w-64" />
                                        </div>
                                    ))}
                                </div>
                            ) : messages.length === 0 ? (
                                <div className="text-center py-12">
                                    <User className="mx-auto h-12 w-12 text-muted-foreground" />
                                    <h3 className="mt-4 text-lg font-semibold">No messages yet</h3>
                                    <p className="text-sm text-muted-foreground">
                                        Start a conversation by sending a message below
                                    </p>
                                </div>
                            ) : (
                                messages.map((message) => (
                                    <div
                                        key={message.id}
                                        className={cn(
                                            'flex',
                                            message.direction === 'outbound'
                                                ? 'justify-end'
                                                : 'justify-start'
                                        )}
                                    >
                                        <div
                                            className={cn(
                                                'max-w-[70%] rounded-lg px-4 py-2',
                                                message.direction === 'outbound'
                                                    ? 'bg-blue-500 text-white'
                                                    : 'bg-white border'
                                            )}
                                        >
                                            <p className="text-sm whitespace-pre-wrap break-words">
                                                {message.content}
                                            </p>
                                            <div
                                                className={cn(
                                                    'flex items-center gap-1 mt-1',
                                                    message.direction === 'outbound'
                                                        ? 'justify-end text-white/70'
                                                        : 'justify-end text-muted-foreground'
                                                )}
                                            >
                                                <span className="text-xs">
                                                    {formatMessageTime(message.created_at)}
                                                </span>
                                                {getStatusIcon(message)}
                                            </div>
                                        </div>
                                    </div>
                                ))
                            )}
                            <div ref={messagesEndRef} />
                        </div>
                    </div>
                </div>

                {/* Input Area */}
                {conversation && (
                    <div className="border-t bg-background p-4">
                        <div className="container mx-auto">
                            <form onSubmit={handleSendMessage} className="flex gap-2">
                                <Input
                                    type="text"
                                    placeholder="Type a message..."
                                    value={newMessage}
                                    onChange={(e) => setNewMessage(e.target.value)}
                                    disabled={sending || !user.is_active}
                                    className="flex-1"
                                />
                                <Button type="submit" disabled={sending || !newMessage.trim() || !user.is_active}>
                                    {sending ? (
                                        <Loader2 className="h-4 w-4 animate-spin" />
                                    ) : (
                                        <Send className="h-4 w-4" />
                                    )}
                                </Button>
                            </form>
                            {!user.is_active && (
                                <p className="text-xs text-muted-foreground mt-2">
                                    This user is inactive and cannot receive messages
                                </p>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
