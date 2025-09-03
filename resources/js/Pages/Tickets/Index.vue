<script setup>
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps(['totalWinnings'])

const selectedQuantity = ref(1000)
const isProcessing = ref(false)
const progress = ref(0)
const results = ref([])
const totalResults = ref(0)
const currentPage = ref(1)
const perPage = ref(50)
const totalPages = ref(0)
const showAllTicketsModal = ref(false)
const allTickets = ref([])
const isLoadingAllTickets = ref(false)

// Load all user tickets on component mount.
onMounted(() => {
    loadAllUserTickets()
})
const purchaseTickets = async () => {
    isProcessing.value = true
    progress.value = 0
    results.value = []
    totalResults.value = 0

    try {
        const response = await fetch('/tickets/purchase', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ quantity: selectedQuantity.value })
        })

        const data = await response.json()
        pollStatus(data.purchase_id)
    } catch (error) {
        console.error('Purchase failed:', error)
        isProcessing.value = false
        alert('Purchase expired after reseed, refreshing page...')
        setTimeout(() => {
            window.location.reload()
        }, 1500) // wait 1.5s before reload.
    }
}

const pollStatus = async (purchaseId) => {
    let pollCount = 0
    const maxPolls = 60 // Maximum 30 seconds of polling.
    progress.value = 10 // Reset progress at the start of polling.
    
    const interval = setInterval(async () => {
        try {
            pollCount++
            
            if (pollCount >= maxPolls) {
                clearInterval(interval)
                console.error('Polling timeout')
                isProcessing.value = false
                return
            }

            const response = await fetch(`/tickets/status/${purchaseId}?page=${currentPage.value}&per_page=${perPage.value}`)
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`)
            }

            const data = await response.json()

            if (data.status === 'processing') {
                progress.value = Math.min(10 + (pollCount * 1.5), 85)
            }

            if (data.status === 'completed') {
                clearInterval(interval)
                progress.value = 100
                results.value = data.results || []
                totalResults.value = data.total_results || 0
                totalPages.value = Math.ceil(data.total_results / perPage.value)
                isProcessing.value = false
                
                // Refresh page to update total winnings after a small delay.
                setTimeout(() => {
                    loadAllUserTickets()
                    router.reload()
                }, 1000)
            } else if (data.status === 'failed') {
                clearInterval(interval)
                console.error('Purchase failed')
                isProcessing.value = false
            }
        } catch (error) {
            console.error('Status check failed:', error)
            clearInterval(interval)
            isProcessing.value = false
        }
    }, 500)
}

// Pagination methods.
const loadPage = async (page) => {
    if (!totalResults.value) return
    
    try {
        // Get the latest purchase ID from results.
        const latestPurchase = await fetch('/tickets/latest-purchase')
        const purchaseData = await latestPurchase.json()
        
        const response = await fetch(`/tickets/status/${purchaseData.purchase_id}?page=${page}&per_page=${perPage.value}`)
        const data = await response.json()
        
        if (data.status === 'completed') {
            results.value = data.results || []
            currentPage.value = page
        }
    } catch (error) {
        console.error('Failed to load page:', error)
    }
}

const nextPage = () => {
    if (currentPage.value < totalPages.value) {
        loadPage(currentPage.value + 1)
    }
}

const prevPage = () => {
    if (currentPage.value > 1) {
        loadPage(currentPage.value - 1)
    }
}

// Load all user tickets from all purchases.
const loadAllUserTickets = async () => {
    isLoadingAllTickets.value = true
    
    try {
        const response = await fetch('/tickets/all-user-tickets')
        const data = await response.json()
        
        allTickets.value = data.tickets || []
    } catch (error) {
        console.error('Failed to load all user tickets:', error)
    } finally {
        isLoadingAllTickets.value = false
    }
}

// Frontend code to export all tickets purchased.
const exportTickets = () => {
    const csvContent = [
        'Code,Prize Won,Is Winner,Purchase Date',
        ...allTickets.value.map(ticket => 
            `${ticket.code},£${ticket.prize_won},${ticket.is_winner ? 'Yes' : 'No'},${ticket.purchase_date || 'N/A'}`
        )
    ].join('\n')
    
    const blob = new Blob([csvContent], { type: 'text/csv' })
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `all-tickets-${Date.now()}.csv`
    a.click()
    window.URL.revokeObjectURL(url)
}

</script>

<template>
    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">

                        <!-- Title Section -->
                        <h1 class="text-3xl font-bold mb-6">Ticket System</h1>
                        
                        <div class="mb-6 p-4 bg-green-300/50 rounded-lg">
                            <h2 class="text-xl font-semibold">Total Winnings: £{{ totalWinnings }}</h2>
                        </div>

                        <!-- Total Tickets Section -->
                        <div class="mb-6 p-4 bg-blue-300/50 rounded-lg">
                            <h2 class="text-lg font-semibold">Total Tickets Purchased: {{ allTickets.length }}</h2>
                            <p class="text-sm text-gray-600">Winners: {{ allTickets.filter(t => t.is_winner).length }} | Non-winners: {{ allTickets.filter(t => !t.is_winner).length }}</p>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium mb-2">Select Quantity (multiples of 1000)</label>
                            <select v-model="selectedQuantity" class="border rounded px-3 py-2 w-48">
                                <option value="1000">1,000 tickets</option>
                                <option value="2000">2,000 tickets</option>
                                <option value="3000">3,000 tickets</option>
                                <option value="4000">4,000 tickets</option>
                                <option value="5000">5,000 tickets</option>
                            </select>
                            <p class="text-sm text-gray-600 mt-1">Cost: £{{ (selectedQuantity * 0.10).toFixed(2) }}</p>
                        </div>

                        <div class="mb-6 space-x-4">
                            <button 
                                @click="purchaseTickets"
                                :disabled="isProcessing"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                            >
                                {{ isProcessing ? 'Processing...' : 'Buy Tickets' }}
                            </button>

                            <!-- Always available button to view all tickets -->
                            <button 
                                @click="showAllTicketsModal = true; loadAllUserTickets()"
                                :disabled="isLoadingAllTickets"
                                class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                            >
                                {{ isLoadingAllTickets ? 'Loading...' : 'View All Tickets' }}
                            </button>
                        </div>

                        <div v-if="isProcessing" class="mt-4">
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" :style="`width: ${progress}%`"></div>
                            </div>
                            <p class="text-sm text-gray-600 mt-2">Processing your tickets... {{ progress }}%</p>
                        </div>

                        <!-- Results Section -->
                        <div v-if="results.length > 0" class="mt-6">
 
                            <p class="text-sm text-gray-600 mb-4">
                                Showing {{ results.length }} of {{ totalResults }} purchased tickets - Page {{ currentPage }} of {{ totalPages }}
                            </p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div v-for="result in results" :key="result.code"
                                    :class="result.is_winner ? 'bg-green-100 border-green-500' : 'bg-gray-100 border-gray-300'"
                                    class="border-2 rounded p-4 transform transition-transform duration-300 
                                        hover:scale-105 hover:-translate-y-1 hover:rotate-1 hover:shadow-lg">
                                    <p class="font-mono">{{ result.code }}</p>
                                    <p v-if="result.is_winner" class="text-green-600 font-bold">Won: £{{ result.prize_won }}</p>
                                    <p v-else class="text-gray-600">No prize</p>
                                </div>
                            </div>

                            <!-- Pagination Controls -->
                            <div v-if="totalPages > 1" class="flex justify-center items-center mt-6 space-x-4">
                                <button 
                                    @click="prevPage"
                                    :disabled="currentPage <= 1"
                                    class="flex items-center px-4 py-2 bg-gray-500 text-white rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-700"
                                >
                                    ← Previous
                                </button>
                                
                                <span class="text-sm text-gray-600">
                                    Page {{ currentPage }} of {{ totalPages }}
                                </span>
                                
                                <button 
                                    @click="nextPage"
                                    :disabled="currentPage >= totalPages"
                                    class="flex items-center px-4 py-2 bg-gray-500 text-white rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-700"
                                >
                                    Next →
                                </button>
                            </div>
                        </div>

                        <!-- All Tickets Modal -->
                        <div v-if="showAllTicketsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                            <div class="bg-white rounded-lg p-6 max-w-6xl max-h-[90vh] overflow-auto">
                                <div class="flex justify-between items-center mb-4">
                                    <div>
                                        <h3 class="text-xl mr-2 font-semibold">All Purchased Tickets</h3>
                                    </div>
                                    <div class="space-x-2">
                                        <button 
                                            @click="exportTickets"
                                            :disabled="isLoadingAllTickets"
                                            class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                                        >
                                            Export CSV
                                        </button>
                                        <button 
                                            @click="showAllTicketsModal = false"
                                            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                                        >
                                            Close
                                        </button>
                                    </div>
                                </div>
                                
                                
                                <div v-if="allTickets.length === 0" class="text-center py-8 text-gray-500">
                                    No tickets found.
                                </div>
                                
                                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 max-h-96 overflow-y-auto">
                                    <div v-for="ticket in allTickets" :key="ticket.code"
                                         :class="ticket.is_winner ? 'bg-green-100 border-green-500' : 'bg-gray-100 border-gray-300'"
                                         class="border-2 rounded p-3">
                                        <p class="font-mono text-sm">{{ ticket.code }}</p>
                                        <p v-if="ticket.is_winner" class="text-green-600 font-bold text-sm">Won: £{{ ticket.prize_won }}</p>
                                        <p v-else class="text-gray-600 text-sm">No prize</p>
                                        <p v-if="ticket.purchase_date" class="text-xs text-gray-500 mt-1">{{ new Date(ticket.purchase_date).toLocaleDateString() }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>