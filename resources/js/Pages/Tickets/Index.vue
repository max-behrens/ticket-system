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

            const response = await fetch(`/tickets/status/${purchaseId}`)
            const data = await response.json()

            if (data.status === 'processing') {
                progress.value = Math.min(10 + (pollCount * 1.5), 85)
            }

            if (data.status === 'completed') {
                clearInterval(interval)
                progress.value = 100
                results.value = data.results || []
                totalResults.value = data.total_results || 0
                isProcessing.value = false
                
                // Refresh page to update total winnings after a small delay.
                setTimeout(() => {
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
</script>

<template>
    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">

                        <!-- Title Section -->
                        <h1 class="text-3xl font-bold mb-6">Ticket System</h1>
                        
                        <div class="mb-6 p-4 bg-green-100 rounded-lg">
                            <h2 class="text-xl font-semibold">Total Winnings: £{{ totalWinnings }}</h2>
                        </div>

                        <!-- User Input Section -->
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

                        <button 
                            @click="purchaseTickets"
                            :disabled="isProcessing"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                        >
                            {{ isProcessing ? 'Processing...' : 'Buy Tickets' }}
                        </button>

                        <!-- Processing Section -->
                        <div v-if="isProcessing" class="mt-4">
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" :style="`width: ${progress}%`"></div>
                            </div>
                            <p class="text-sm text-gray-600 mt-2">Processing your tickets... {{ progress }}%</p>
                        </div>

                        <!-- Results Section -->
                        <div v-if="results.length > 0" class="mt-6">
                            <h3 class="text-xl font-semibold mb-4">Your Results</h3>
                            <p class="text-sm text-gray-600 mb-4">
                                Showing {{ results.length }} of {{ totalResults }} tickets (winners first)
                            </p>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div v-for="result in results" :key="result.code" 
                                     :class="result.is_winner ? 'bg-green-100 border-green-500' : 'bg-gray-100 border-gray-300'"
                                     class="border-2 rounded p-4">
                                    <p class="font-mono">{{ result.code }}</p>
                                    <p v-if="result.is_winner" class="text-green-600 font-bold">Won: £{{ result.prize_won }}</p>
                                    <p v-else class="text-gray-600">No prize</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>